<?php
namespace mschurr\framework\plugins\CAS;

use \Exception;
use \Request;
use \Response;
use \URL;
use \GuzzleHttp;

/**
 * Central Authentication Service (CAS) Link Library
 *
 * Login Protocol Explanation:
 *   1) User visits login page our website.
 *      IF: the user's session has a CAS user attached, then skip to step (6).
 *   2) User is redirected to $CAS_BASE/login?service=%s
 *   3) User authenticates with CAS and is redirected back to our site with the following parameter:
 *        ticket = (string)
 *   4) Server verifies the login by contacting $CAS_BASE/serviceValidate?ticket=%s&service=%s
 *      CAS server returns XML including user's name <cas:user> and (optional) attributes.
 *      NOTE: Must be able to establish secure SSL connection to the CAS server for this to work.
 *      IF: ticket verification fails (CAS server returns nothing), then display an error page and abort.
 *   5) User's session is regenerated and has the CAS user attached.
 *   6) User is forwarded by our site to final location.
 *
 * Logout Protocol Explanation:
 *   1) User visits logout page on our website.
 *   2) User's session is destroyed.
 *   3) User is redirected to $CAS_BASE/logout?service=%s to terminate their session with CAS.
 *   4) User is redirected back to our site from CAS.
 *   5) User is redirected by our site to final location.
 *
 */

/*****************
 * Example Usage *
 *****************

scope(function() {
  $config = new CASConfig();
  $config->host = 'https://cas.service.com';

  $authenticator = new CASAuthenticator($config);

  Route::get('/login', function(Request $request, Response $response) use (&$authenticator) {
    $destination = isset($request->get['destination']) ? $request->get['destination'] : URL::to('/');

    try {
      $user = $authenticator->startAuthentication($request, $response, $destination);

      if ($user) {
        $request->session->regenerate();

        // ... copy CAS data to the user's session ...
      }
    } catch (CASAuthenticationException $e) {
      return 400; // Invalid Ticket: HTTP 400 Bad Request
    }
  });

  Route::get('/logout', function(Request $request, Response $response) use (&$authenticator) {
    $destination = URL::to('/');

    // ... remove CAS data from the user's session ...

    $authenticator->endAuthentication($response, $destination);
  });
});

*/

class CASUser {
  public /* string */ $username;
  public /* array<string, string> */ $attributes;
}

class CASConfig {
  public /* string */ $host;
  public /* string */ $path = '/cas';
}

class CASException extends Exception {}
class CASAuthenticationException extends CASException {}

class CASAuthenticator {
  const CAS_TICKET_PARAM = 'ticket';
  const CAS_SERVICE_PARAM = 'service';
  const CAS_PATH_LOGIN = '/login';
  const CAS_PATH_LOGOUT = '/logout';
  const CAS_PATH_VERIFY = '/serviceValidate';
  const REDIRECT_PARAM = 'destination';

  protected /* CASConfig */ $config;

  public function __construct(CASConfig $config) /* throws CASException */ {
    $this->config = $config;

    if (!$this->config->host) {
      throw new CASException('Unable to validate configuration');
    }
  }

  protected /* string */ function getCasBase() {
    // NOTE: Assume SSL over 443, because anything else would be plain silly. Hard-coding the SSL certificate is not
    // neccesary, because standard SSL certificate verification should be secure enough for almost every use case.
    return 'https://'.$this->config->host.$this->config->path;
  }

  protected /* string */ function getLoginUrl(/* string|URL */ $destination) {
    return $this->getCasBase().static::CAS_PATH_LOGIN.
      '?'.static::CAS_SERVICE_PARAM.'='.urlencode($this->getServiceUrl($destination));
  }

  protected /* string */ function getLogoutUrl(/* string|URL */ $destination) {
    return $this->getCasBase().static::CAS_PATH_LOGOUT.
      '?'.static::CAS_SERVICE_PARAM.'='.urlencode((string) ($destination));
  }

  protected /* string */ function getVerificationUrl(/* string */ $ticket, /* string|URL */ $destination) {
    return $this->getCasBase().static::CAS_PATH_VERIFY.
      '?'.static::CAS_TICKET_PARAM.'='.urlencode($ticket).
      '&'.static::CAS_SERVICE_PARAM.'='.urlencode($destination);
  }

  protected /* string */ function getServiceUrl(/* string|URL */ $destination) {
    return (string) URL::current()->getBaseUrl()->with(array(
      static::REDIRECT_PARAM => (string) $destination
    ));
  }

  /**
   * Initiates authentication. This will redirect the user away from our site and return them to the provided URL
   * when they have completed authentication.
   */
  public /* CASUser */ function startAuthentication(Request $request, Response $response,
      /* URL|string */ $destination) /* throws CASAuthenticationException */ {
    if ($this->hasToken($request)) {
      $user = $this->verifyAuthentication($request);

      if (!$user) {
        throw new CASAuthenticationException('User provided a bad authentication token');
      }

      $url = URL::to('/');
      if (isset($request->get[static::REDIRECT_PARAM])) {
        $url = URL::to($request->get[static::REDIRECT_PARAM]);
      }

      $response->headers['Location'] = (string) $url;
      $response->status = 302;
      $response->out->clear();

      return $user;
    } else {
      $response->headers['Location'] = $this->getLoginUrl($destination);
      $response->status = 302;
      $response->out->clear();
      $response->send();
      exit(0);
      return null;
    }
  }

  /**
   * Terminates authentication within the CAS server. This will redirect the user to the provided destination if
   * successful.
   */
  public /* void */ function endAuthentication(Response $response, /* URL|string */ $destination) {
    $response->headers['Location'] = $this->getLogoutUrl($destination);
    $response->status = 302;
    $response->out->clear();
  }

  /**
   * Verifies that an HTTP Request has a valid CAS token attached.
   * Returns the user's credentials if so, or null otherwise.
   */
  protected /* CASUser */ function verifyAuthentication(Request $request) {
    if (!$this->hasToken($request)) {
      return null;
    }

    $token = $request->get[static::CAS_TICKET_PARAM];

    $destination = $request->get->has(static::REDIRECT_PARAM)
      ? $request->get[static::REDIRECT_PARAM]
      : URL::to('/');

    $verificationUrl = $this->getVerificationUrl($token, $this->getServiceUrl($destination));

    $client = new GuzzleHttp\Client();
    $response = $client->get($verificationUrl);

    if ($response->getStatusCode() != 200) {
      return null;
    }

    $data = (string) $response->getBody();

    if (strpos($data, "<cas:authenticationSuccess>") === false) {
      return null;
    }

    $matches = [];
    if (preg_match("/\<cas:user\>([A-Za-z0-9\_]+)\<\/cas:user\>/s", $data, $matches) !== 1) {
      return null;
    }

    $username = $matches[1];

    $user = new CASUser();
    $user->username = $username;
    $user->attributes = array();
    return $user;
  }

  /**
   * Returns whether or not the request has a CAS token attached.
   */
  protected /* boolean */ function hasToken(Request $request) {
    return $request->get->has(static::CAS_TICKET_PARAM) &&
           strlen($request->get[static::CAS_TICKET_PARAM]) > 0 &&
           strlen($request->get[static::CAS_TICKET_PARAM]) < 255;
  }
}
