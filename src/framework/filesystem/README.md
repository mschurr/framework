php-file-object
===============

This file wrapper class that focuses on providing a powerful, intuitive, and expressive alternative to the built in procedural functions and SplFileInfo/SplFileObject classes.

## Installation

This package will be made available via Composer in the near future.

## Usage

These are some sample code snippets. The complete documentation can be found in the source code.

```php
// Instantiate a new file object. This does not touch the disk at all; properties are lazy loaded.
$file = File::open('./file.txt');

// The object uses magic properties to convienently access info about the file.
// First, we can check if the file exists...
if($file->exists) {
  // Count the number of lines in the file.
  echo 'The file has '.count($file->lines).' lines.';

  // Iterate through each line in the file (only one line is held in memory at a time).
  foreach($file->lines as $line) {
    echo $line;
  }

  // We can also print some useful info.
  echo $file->size.' '.$file->formattedSize.PHP_EOL;
  echo $file->mime.PHP_EOL;
}
// Otherwise, if the file doesn't exist...
else {
  // Let's create it with some initial content.
  $file->create('Initial Content!');
}

// Some properties can also be modified, performing actions on the file.
$file->extension = 'json';

// Directories can also be represented as File objects.
$folder = File::open('./');

// We can iterate through the directory's children.
foreach($folder as $file) {
  echo $file->name.PHP_EOL;
}

// Or through the directory's descendants (recursive iteration).
foreach($folder->descendants as $file) {
  echo $file->name.PHP_EOL;
}

// It is also possible to filter the iterator.
// If we want to iterate through all .php files contained in a directory (recursively):
$filter = function(File $file) {
  // We can access any properties of a File object here, and filter based off of them.
  if($file->isFile && $file->extension === 'php')
    return true;
  return false;
};

foreach($folder->descendants($filter) as $folder) {
  echo $folder->canonicalPath.PHP_EOL;
}

// Find all empty subdirectories and delete them.
$folder = File::open(__DIR__);
foreach($folder->descendants(function(File &$file){
  return $file->isDirectory && count($file->children) === 0;
}) as $emptySubdirectory) {
  $emptySubdirectory->delete();
}

```

## Wrapping Uploaded Files

You may wish to use this class to handle uploaded files. To do this you can use the static method, `uploadedFiles`. If you wish to integrate this with an existing system, take a look at the `uploadedFiles` method implementation.

The method will return an array mapping of input names to File objects. The file objects point to the temporary files that PHP created when the files were uploaded. In addition to their normal properties, File objects originating from uploads also have the following properties:
  
  * (bool) `uploaded`
  * (string) `uploadedName`
  * (string) `uploadedExtension`
  * (string) `uploadedMime`

The temporary files the PHP creates often do not have extensions; usage to `$file->extension` will likely return null for an uploaded file. However, `$file->uploadedExtension` will return the extension provided by the user.

```php
// Get all files attached to the request as an array of File objects.
$uploaded = File::uploadedFiles();

// Iterate through the uploaded files.
foreach($uploaded as $file) {
  // If the file is too large, don't do anything.
  if($file->size >= pow(10, 8))
    continue;
    
  // If the file is not an image, don't do anything.
  if(!$file->isImage)
    continue;

  // The file passes validation; move it to a safe directory.
  try {
    $file->moveTo(STORAGE_PATH.'/'.$file->md5.'.'.$file->uploadedExtension);
  }
  catch(FileAlreadyExistsException $e) {
    // Someone has already uploaded a copy of the file (the md5 hashes match).
    // Do nothing and proceed normally.
  }
  catch(FileException $e) {
    // Something else went wrong. Handle the error here.
  }
}
```
