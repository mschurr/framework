auth_tokens {
	userid,
	token_hash,
	expires
}

auth_attempts {
	ipaddress,
	userid,
	timestamp,
	successful,
	fraudulent
}

auth_sessions {
	userid,
	token_hash,
	expires
}

users {
	userid
	username
	email
	banned
	password_cost
	password_salt
	password_hash
	properties
}

user_privelages {
	userid, privelageid
}

group_membership {
	userid, groupid
}

groups {
	groupid, name
}

group_privelages {
	groupid, privelageid
}