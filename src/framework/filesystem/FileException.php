<?php

class FileException extends RuntimeException
{
	// A generic file exception.
}

class FileAlreadyExistsException extends FileException
{
	// An operation failed because a file already existed at a given location.
}

class FileDoesNotExistException extends FileException
{
	// An operation failed because a file did not exist.
}

class FileNotReadableException extends FileException
{
	// An operation failed because a file was not readable.
}

class FileNotWritableException extends FileException
{
	// An operation failed because a file was not writable.
}

class FileOperationInvalidException extends FileException
{
	// The requested operation can not be performed on a given file type.
	// e.g. calling file->lines on a directory
}

class FileOperationFailedException extends FileException
{
	// A file operation failed due to some unexpected reason.
}

class FileMemoryInsufficientException extends FileException
{
	// There is not enough space in memory (PHP) to perform an operation.
}

class FileDiskSpaceInsufficientException extends FileException
{
	// There is not enough free disk space to perform an operation.
}
