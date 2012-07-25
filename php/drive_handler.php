<?php
/**
 * Class for accessing the Google Drive API, including retrieving,
 * creating and updating files stored in Google Drive.
 * 
 * Copyright 2012 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once 'libs/gd-v2-php/apiClient.php';
require_once 'libs/gd-v2-php/contrib/apiDriveService.php';

require_once 'oauth_credentials.php';

/**
 * Class for accessing the Google Drive API, including retrieving,
 * creating and updating files stored in Google Drive.
 *
 * @author Ryan Boyd <rboyd@google.com>
 */
class DriveHandler {

  /**
   * Google API service class for interacting with Drive API
   *
   * @var apiDriveService
   */
  private $service;

  /**
   * Construct a DriveHandler, building the service object from the passed
   * OAuth credentials.
   *
   * @param OauthCredentials $credentials OAuth credentials
   * @return DriveHandler the constructed object
   */
  function DriveHandler($credentials) {
    $this->service = $this->BuildService($credentials);
    return $this;
  }

  /**
   * Build a Drive service object for interacting with the Google Drive API.
   *
   * @return apiDriveService service object
   */
  function BuildService($credentials) {
    $client = new apiClient();
    // return data from API calls as PHP objects instead of arrays
    $client->setUseObjects(true);
    error_log(print_r($credentials, 1));
    $client->setAccessToken($credentials->toJson());
    // set clientId and clientSecret in case token is expired 
    // and refresh is needed
    $client->setClientId($credentials->clientId);
    $client->setClientSecret($credentials->clientSecret);
    return new apiDriveService($client);
  }
  
  /**
   * Retrieve a file metadata and content from Drive.
   *
   * @param string $fileId ID for the file to retrieve from Drive
   * @return string JSON string representation of file metadata and content
   */
  function GetFile($fileId) {
    $fileVars = null;
    try {
      /*
       * Retrieve metadata for the file specified by $fileId.
       */
      $file = $this->service->files->get($fileId);
      $fileVars = get_object_vars($file);
  
      /*
       * Retrieve the file's content using download URL specified in metadata.
       */
      $request = new apiHttpRequest($file->downloadUrl, 'GET', null, null);
      $httpRequest = apiClient::$io->authenticatedRequest($request);
      $content = $httpRequest->getResponseBody();
      $fileVars['content'] = $content?($content):'';
    } catch (apiServiceException $e) {
      /*
       * Log error and re-throw
       */
      error_log('Error retrieving file from Drive: ' . $e->getMessage(), 0);
      throw $e;
    }
    return json_encode($fileVars);
  }
  
  /**
   * Save an updated file to drive, including metadata and content.
   *
   * @param string $fileId ID representing file to save to Drive
   * @param DriveFile $inputFile File to be updated 
   * @return DriveFile File object representing new file
   */
  function SaveUpdatedFile($fileId, $inputFile) {
    try {
      $file = $this->service->files->get($fileId);
      $file->setTitle($inputFile->title);
      $file->setDescription($inputFile->description);
      $optParams = array('newRevision' => true);
      $updatedFile = $this->service->files->update($fileId, $file,
          array('data' => $inputFile->content, 'mimeType' => $file->mimeType));
      return $updatedFile;
    } catch (apiServiceException $e) {
      /*
       * Log error and re-throw
       */
      error_log('Error saving updated file to Drive: ' . $e->getMessage(), 0);
      throw $e;
    }
  }
  
  /**
   * Save a new file to drive, including metadata and content.
   *
   * @param DriveFile $inputFile File to be updated 
   * @return DriveFile File object representing new file
   */
  function SaveNewFile($inputFile) {
    try {
      $mimeType = 'text/plain';
      $file = new DriveFile();
      $file->setTitle($inputFile->title);
      $file->setDescription($inputFile->description);
      $file->setMimeType($mimeType);
      // Set the parent folder.
      if ($inputFile->parentId != null) {
        $parentsCollectionData = new DriveFileParentsCollection();
        $parentsCollectionData->setId($inputFile->parentId);
        $file->setParentsCollection(array($parentsCollectionData));
      }
      $createdFile = $this->service->files->insert($file, array(
          'data' => $inputFile->content,
          'mimeType' => $mimeType,
      ));
      return $createdFile;
    } catch (apiServiceException $e) {
      /*
       * Log error and re-throw
       */
      error_log('Error saving new file to Drive: ' . $e->getMessage(), 0);
      throw $e;
    }
  }
  
  /**
   * Save a file to drive, including metadata and content.
   *
   * @param string $fileId ID representing file to save to Drive
   * @param DriveFile $inputFile File to be updated
   * @return DriveFile File object representing saved content
   */
  function SaveFile($fileId, $inputFile) {
    if ($fileId != '') {
      return $this->SaveUpdatedFile($fileId, $inputFile);
    } else {
      $createdFile = $this->SaveNewFile($inputFile);
      $_SESSION['fileId'] = $createdFile->id;
      return $createdFile;
    }
  }

  function GetAbout() {
    try {
      return $this->service->about->get();
    } catch (apiServiceException $e) {
      /*
       * Log error and re-throw
       */
      error_log('Error getting about from Drive: ' . $e->getMessage(), 0);
      throw $e;
    }
  }
}
?>
