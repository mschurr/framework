/* Auth-DB Driver */
DROP TABLE IF EXISTS `auth_tokens`;
DROP TABLE IF EXISTS `auth_attempts`;
DROP TABLE IF EXISTS `auth_sessions`;

CREATE TABLE `auth_tokens` (
  `userid` int(64) UNSIGNED NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires` int(64) UNSIGNED NOT NULL,
  PRIMARY KEY  (`userid`, `token_hash`)
);

CREATE TABLE `auth_sessions` (
  `userid` int(64) UNSIGNED NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires` int(64) UNSIGNED NOT NULL,
  PRIMARY KEY  (`userid`, `token_hash`)
);

CREATE TABLE `auth_attempts` (
  `ipaddress` varchar(40) NOT NULL,
  `userid` int(64) UNSIGNED NOT NULL,
  `timestamp` int(64) UNSIGNED NOT NULL,
  `successful` tinyint(1) UNSIGNED NOT NULL,
  `fraudulent` tinyint(1) UNSIGNED NOT NULL
);

CREATE INDEX `auth_attempts_ipaddress` ON `auth_attempts` (`ipaddress`);
CREATE INDEX `auth_attempts_userid` ON `auth_attempts` (`userid`);

/* UserService-DB Driver */
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `user_privileges`;

CREATE TABLE `users` (
  `userid` int(64) UNSIGNED NOT NULL auto_increment,
  `username` varchar(32) NOT NULL,
  `email` varchar(64) NOT NULL,
  `banned` int(64) UNSIGNED NOT NULL DEFAULT '0',
  `password_cost` int(64) UNSIGNED NOT NULL,
  `password_salt` varchar(64) NOT NULL,
  `password_hash` varchar(128) NOT NULL,
  `properties` text NOT NULL DEFAULT '',
  PRIMARY KEY  (`userid`)
);

CREATE TABLE `user_privileges` (
  `userid` int(64) UNSIGNED NOT NULL,
  `privilegeid` int(64) UNSIGNED NOT NULL,
  PRIMARY KEY  (`userid`, `privilegeid`)
);

/* GroupService-DB Driver */
DROP TABLE IF EXISTS `groups`;
DROP TABLE IF EXISTS `group_membership`;
DROP TABLE IF EXISTS `group_privileges`;

CREATE TABLE `groups` (
  `groupid` int(64) UNSIGNED NOT NULL auto_increment,
  `name` varchar(64) NOT NULL,
  PRIMARY KEY  (`groupid`)
);

CREATE TABLE `group_membership` (
  `groupid` int(64) UNSIGNED NOT NULL,
  `userid` int(64) UNSIGNED NOT NULL,
  PRIMARY KEY  (`groupid`, `userid`)
);

CREATE TABLE `group_privileges` (
  `groupid` int(64) UNSIGNED NOT NULL,
  `privilegeid` int(64) UNSIGNED NOT NULL,
  PRIMARY KEY  (`groupid`, `privilegeid`)
);