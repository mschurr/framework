<?php
if(!function_exists('included'))
	die();

// Queue::push(Controller[@Method] default fire(job,data),array data) ::push(function(job,data)) | queue Job Object: ->release(waitseconds=now) ->attempts() ->getJobID() ->delete()
?>