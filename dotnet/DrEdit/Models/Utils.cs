/*
 * Copyright (c) 2012 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except
 * in compliance with the License. You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under the License
 * is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express
 * or implied. See the License for the specific language governing permissions and limitations under
 * the License.
 */

using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using Google.Apis.Authentication;
using Google.Apis.Authentication.OAuth2.DotNetOpenAuth;
using DotNetOpenAuth.OAuth2;
using Google.Apis.Authentication.OAuth2;
using Google.Apis.Oauth2.v2;
using Google.Apis.Oauth2.v2.Data;
using Google;
using System.Collections.Specialized;
using System.Net;
using System.IO;
using Google.Apis.Requests;
using System.Text;
using DotNetOpenAuth.Messaging;

namespace DrEdit.Models
{
    public class Utils
    {
        /// <summary>
        /// Build a Drive service object.
        /// </summary>
        /// <param name="credentials">OAuth 2.0 credentials.</param>
        /// <returns>Drive service object.</returns>
        internal static Google.Apis.Drive.v1.DriveService BuildService(IAuthenticator credentials)
        {
            return new Google.Apis.Drive.v1.DriveService(credentials);
        }

        /// <summary>
        /// Retrieve an IAuthenticator instance using the provided state.
        /// </summary>
        /// <param name="credentials">OAuth 2.0 credentials to use.</param>
        /// <returns>Authenticator using the provided OAuth 2.0 credentials</returns>
        public static IAuthenticator GetAuthenticatorFromState(IAuthorizationState credentials)
        {
            var provider = new StoredStateClient(GoogleAuthenticationServer.Description, ClientCredentials.CLIENT_ID, ClientCredentials.CLIENT_SECRET, credentials);
            var auth = new OAuth2Authenticator<StoredStateClient>(provider, StoredStateClient.GetState);
            auth.LoadAccessToken();
            return auth;
        }

        /// <summary>
        /// Retrieved stored credentials for the provided user ID.
        /// </summary>
        /// <param name="userId">User's ID.</param>
        /// <returns>Stored GoogleAccessProtectedResource if found, null otherwise.</returns>
        static IAuthorizationState GetStoredCredentials(String userId)
        {
            StoredCredentialsDBContext db = new StoredCredentialsDBContext();
            StoredCredentials sc = db.StoredCredentialSet.FirstOrDefault(x => x.UserId == userId);
            if (sc != null)
            {
                return new AuthorizationState() { AccessToken = sc.AccessToken, RefreshToken = sc.RefreshToken };
            }
            return null;
        }

        /// <summary>
        /// Store OAuth 2.0 credentials in the application's database.
        /// </summary>
        /// <param name="userId">User's ID.</param>
        /// <param name="credentials">The OAuth 2.0 credentials to store.</param>
        static void StoreCredentials(String userId, IAuthorizationState credentials)
        {
            StoredCredentialsDBContext db = new StoredCredentialsDBContext();
            StoredCredentials sc = db.StoredCredentialSet.FirstOrDefault(x => x.UserId == userId);
            if (sc != null)
            {
                sc.AccessToken = credentials.AccessToken;
                sc.RefreshToken = credentials.RefreshToken;
            }
            else
            {
                db.StoredCredentialSet.Add(new StoredCredentials { UserId = userId, AccessToken = credentials.AccessToken, RefreshToken = credentials.RefreshToken });
            }
            db.SaveChanges();
        }

        /// <summary>
        /// Exchange an authorization code for OAuth 2.0 credentials.
        /// </summary>
        /// <param name="authorizationCode">Authorization code to exchange for OAuth 2.0 credentials.</param>
        /// <returns>OAuth 2.0 credentials.</returns>
        /// <exception cref="CodeExchangeException">An error occurred.</exception>
        static IAuthorizationState ExchangeCode(String authorizationCode)
        {
            var provider = new NativeApplicationClient(GoogleAuthenticationServer.Description, ClientCredentials.CLIENT_ID, ClientCredentials.CLIENT_SECRET);
            IAuthorizationState state = new AuthorizationState();
            state.Callback = new Uri(ClientCredentials.REDIRECT_URI);
            try
            {
                state = provider.ProcessUserAuthorization(authorizationCode, state);
                return state;
            }
            catch (ProtocolException)
            {
                throw new CodeExchangeException(null);
            }
        }

        /// <summary>
        /// Send a request to the UserInfo API to retrieve the user's information.
        /// </summary>
        /// <param name="credentials">OAuth 2.0 credentials to authorize the request.</param>
        /// <returns>User's information.</returns>
        /// <exception cref="NoUserIdException">An error occurred.</exception>
        static Userinfo GetUserInfo(IAuthorizationState credentials)
        {
            Oauth2Service userInfoService = new Oauth2Service(GetAuthenticatorFromState(credentials));
            Userinfo userInfo = null;
            try
            {
                userInfo = userInfoService.Userinfo.Get().Fetch();
            }
            catch (GoogleApiRequestException e)
            {
                Console.WriteLine("An error occurred: " + e.Message);
            }

            if (userInfo != null && !String.IsNullOrEmpty(userInfo.Id))
            {
                return userInfo;
            }
            else
            {
                throw new NoUserIdException();
            }
        }

        /// <summary>
        /// Retrieve the authorization URL.
        /// </summary>
        /// <param name="emailAddress">User's e-mail address.</param>
        /// <param name="state">State for the authorization URL.</param>
        /// <returns>Authorization URL to redirect the user to.</returns>
        public static String GetAuthorizationUrl(String emailAddress, String state)
        {
            var provider = new NativeApplicationClient(GoogleAuthenticationServer.Description);
            provider.ClientIdentifier = ClientCredentials.CLIENT_ID;

            IAuthorizationState authorizationState = new AuthorizationState(ClientCredentials.SCOPES);
            authorizationState.Callback = new Uri(ClientCredentials.REDIRECT_URI);

            UriBuilder builder = new UriBuilder(provider.RequestUserAuthorization(authorizationState));
            NameValueCollection queryParameters = HttpUtility.ParseQueryString(builder.Query);

            queryParameters.Set("access_type", "offline");
            queryParameters.Set("approval_prompt", "force");
            queryParameters.Set("user_id", emailAddress);
            queryParameters.Set("state", state);

            builder.Query = queryParameters.ToString();
            return builder.Uri.ToString();
        }

        /// <summary>
        /// Retrieve credentials using the provided authorization code.
        ///
        /// This function exchanges the authorization code for an access token and
        /// queries the UserInfo API to retrieve the user's e-mail address. If a
        /// refresh token has been retrieved along with an access token, it is stored
        /// in the application database using the user's e-mail address as key. If no
        /// refresh token has been retrieved, the function checks in the application
        /// database for one and returns it if found or throws a NoRefreshTokenException
        /// with the authorization URL to redirect the user to.
        /// </summary>
        /// <param name="authorizationCode">Authorization code to use to retrieve an access token.</param>
        /// <param name="state">State to set to the authorization URL in case of error.</param>
        /// <returns>OAuth 2.0 credentials instance containing an access and refresh token.</returns>
        /// <exception cref="CodeExchangeException">
        /// An error occurred while exchanging the authorization code.
        /// </exception>
        /// <exception cref="NoRefreshTokenException">
        /// No refresh token could be retrieved from the available sources.
        /// </exception>
        public static IAuthenticator GetCredentials(String authorizationCode, String state)
        {
            String emailAddress = "";
            try
            {
                IAuthorizationState credentials = ExchangeCode(authorizationCode);
                Userinfo userInfo = GetUserInfo(credentials);
                String userId = userInfo.Id;
                emailAddress = userInfo.Email;
                if (!String.IsNullOrEmpty(credentials.RefreshToken))
                {
                    StoreCredentials(userId, credentials);
                    return GetAuthenticatorFromState(credentials);
                }
                else
                {
                    credentials = GetStoredCredentials(userId);
                    if (credentials != null && !String.IsNullOrEmpty(credentials.RefreshToken))
                    {
                        return GetAuthenticatorFromState(credentials);
                    }
                }
            }
            catch (CodeExchangeException e)
            {
                Console.WriteLine("An error occurred during code exchange.");
                // Drive apps should try to retrieve the user and credentials for the current
                // session.
                // If none is available, redirect the user to the authorization URL.
                e.AuthorizationUrl = GetAuthorizationUrl(emailAddress, state);
                throw e;
            }
            catch (NoUserIdException)
            {
                Console.WriteLine("No user ID could be retrieved.");
            }
            // No refresh token has been retrieved.
            String authorizationUrl = GetAuthorizationUrl(emailAddress, state);
            throw new NoRefreshTokenException(authorizationUrl);
        }

        /// <summary>
        /// Download a file and return a string with its content.
        /// </summary>
        /// <param name="auth">Authenticator responsible for creating web requests.</param>
        /// <param name="downloadUrl">Url to be used to download the resource.</param>
        public static string DownloadFile(IAuthenticator auth, String downloadUrl)
        {
            string result = "";
            try
            {
                HttpWebRequest request = auth.CreateHttpWebRequest("GET", new Uri(downloadUrl));

                HttpWebResponse response = (HttpWebResponse)request.GetResponse();
                System.IO.Stream stream = response.GetResponseStream();
                StreamReader reader = new StreamReader(stream);
                return reader.ReadToEnd();
            }
            catch (Exception e)
            {
                Console.WriteLine("An error occurred: " + e.Message);
            }
            return result;
        }

        /// <summary>
        /// Update both metadata and content of a file and return the updated file.
        /// </summary>
        public static Google.Apis.Drive.v1.Data.File UpdateResource(Google.Apis.Drive.v1.DriveService service, Authenticator auth, String fileId, String newTitle,
            String newDescription, String newMimeType, String content, bool newRevision)
        {
            Google.Apis.Drive.v1.Data.File temp = updateMetadata(service, fileId, newTitle, newDescription, newMimeType, false);
            if (!string.IsNullOrWhiteSpace(content))
            {
                temp = updateFile(service, auth, temp.Id, temp.MimeType, content, newRevision);
            }
            return temp;
        }

        /// <summary>
        /// Update an existing file's metadata.
        /// </summary>
        /// <param name="service">Drive API service instance.</param>
        /// <param name="fileId">ID of the file to update.</param>
        /// <param name="newTitle">New title for the file.</param>
        /// <param name="newDescription">New description for the file.</param>
        /// <param name="newMimeType">New MIME type for the file.</param>
        /// <param name="newRevision">Whether or not to create a new revision for this file.</param>
        /// <returns>Updated file metadata, null is returned if an API error occurred.</returns>
        private static Google.Apis.Drive.v1.Data.File updateMetadata(Google.Apis.Drive.v1.DriveService service, String fileId, String newTitle,
            String newDescription, String newMimeType, bool newRevision)
        {
            try
            {
                // First retrieve the file from the API.
                Google.Apis.Drive.v1.Data.File file = service.Files.Get(fileId).Fetch();

                file.Title = newTitle;
                file.Description = newDescription;
                file.MimeType = newMimeType;

                // Update the file's metadata.
                Google.Apis.Drive.v1.FilesResource.UpdateRequest request = service.Files.Update(file, fileId);
                request.NewRevision = newRevision;
                Google.Apis.Drive.v1.Data.File updatedFile = request.Fetch();

                return updatedFile;
            }
            catch (Exception e)
            {
                Console.WriteLine("An error occurred: " + e.Message);
                return null;
            }
        }

        /// <summary>
        /// Update an existing file's content.
        /// </summary>
        /// <param name="service">Drive API service instance.</param>
        /// <param name="fileId">ID of the file to update.</param>
        /// <param name="newMimeType">New MIME type for the file.</param>
        /// <param name="newFilename">Filename of the new content to upload.</param>
        /// <param name="newRevision">Whether or not to create a new revision for this file.</param>
        /// <returns>Updated file metadata, null is returned if an API error occurred.</returns>
        private static Google.Apis.Drive.v1.Data.File updateFile(Google.Apis.Drive.v1.DriveService service,
            Authenticator auth, String fileId, String newMimeType, String content, bool newRevision)
        {
            try
            {
                HttpWebRequest request = (HttpWebRequest)HttpWebRequest.Create(
                   "https://www.googleapis.com/upload/drive/v1/files/"
                   + fileId + "?newRevision=" + newRevision + "&uploadType=media");
                request.Method = "PUT";

                request.ContentLength = content.Length;
                if (!string.IsNullOrEmpty(newMimeType))
                {
                    request.ContentType = newMimeType;
                }
                auth.ApplyAuthenticationToRequest(request);

                Stream requestStream = request.GetRequestStream();
                using (StreamWriter writer = new StreamWriter(requestStream))
                {
                    writer.Write(content);
                }
                //requestStream.Write(data, 0, data.Length);
                requestStream.Close();

                IResponse response = new Response(request.GetResponse());
                Google.Apis.Drive.v1.Data.File file = service.DeserializeResponse<Google.Apis.Drive.v1.Data.File>(response);

                // Uncomment the following line to print the File ID.
                // Console.WriteLine("File ID: " + file.Id);

                return file;
            }
            catch (Exception e)
            {
                Console.WriteLine("An error occurred: " + e.Message);
                return null;
            }
        }

        /// <summary>
        /// Create a new file and return it.
        /// </summary>
        public static Google.Apis.Drive.v1.Data.File InsertResource(Google.Apis.Drive.v1.DriveService service, Authenticator auth, String title,
            String description, String mimeType, String content)
        {
            Google.Apis.Drive.v1.Data.File temp = insertMetadata(service, title, description, mimeType);
            if (!string.IsNullOrWhiteSpace(content))
            {
                temp = updateFile(service, auth, temp.Id, temp.MimeType, content, true);
            }
            return temp;
        }

        /// <summary>
        /// Insert new file metadata.
        /// </summary>
        /// <param name="service">Drive API service instance.</param>
        /// <param name="title">Title of the file to insert.</param>
        /// <param name="description">Description of the file to insert.</param>
        /// <param name="mimeType">MIME type of the file to insert.</param>
        /// <returns>Inserted file metadata, null is returned if an API error occurred.</returns>
        private static Google.Apis.Drive.v1.Data.File insertMetadata(Google.Apis.Drive.v1.DriveService service, String title, String description, String mimeType)
        {
            // File's metadata.
            Google.Apis.Drive.v1.Data.File body = new Google.Apis.Drive.v1.Data.File();
            body.Title = title;
            body.Description = description;
            body.MimeType = mimeType;

            try
            {
                Google.Apis.Drive.v1.Data.File file = service.Files.Insert(body).Fetch();

                // Uncomment the following line to print the File ID.
                // Console.WriteLine("File ID: " + file.Id);

                return file;
            }
            catch (Exception e)
            {
                Console.WriteLine("An error occurred: " + e.Message);
                return null;
            }
        }
    }

    /// <summary>
    /// Exception thrown when an error occurred while retrieving credentials.
    /// </summary>
    public class GetCredentialsException : Exception
    {
        public String AuthorizationUrl { get; set; }

        /// <summary>
        /// Construct a GetCredentialsException.
        /// </summary>
        /// @param authorizationUrl The authorization URL to redirect the user to.
        public GetCredentialsException(String authorizationUrl)
        {
            this.AuthorizationUrl = authorizationUrl;
        }
    }

    /// <summary>
    /// Exception thrown when no refresh token has been found.
    /// </summary>
    public class NoRefreshTokenException : GetCredentialsException
    {
        /// <summary>
        /// Construct a NoRefreshTokenException.
        /// </summary>
        /// @param authorizationUrl The authorization URL to redirect the user to.
        public NoRefreshTokenException(String authorizationUrl) : base(authorizationUrl)
        {
        }
    }

    /// <summary>
    /// Exception thrown when a code exchange has failed.
    /// </summary>
    public class CodeExchangeException : GetCredentialsException
    {
        /// <summary>
        /// Construct a CodeExchangeException.
        /// </summary>
        /// @param authorizationUrl The authorization URL to redirect the user to.
        public CodeExchangeException(String authorizationUrl) : base(authorizationUrl)
        {
        }
    }

    /// <summary>
    /// Exception thrown when no user ID could be retrieved.
    /// </summary>
    public class NoUserIdException : Exception
    {
    }

    /// <summary>
    /// Extends the NativeApplicationClient class to allow setting of a custom IAuthorizationState.
    /// </summary>
    public class StoredStateClient : NativeApplicationClient
    {
        /// <summary>
        /// Initializes a new instance of the <see cref="StoredStateClient"/> class.
        /// </summary>
        /// <param name="authorizationServer">The token issuer.</param>
        /// <param name="clientIdentifier">The client identifier.</param>
        /// <param name="clientSecret">The client secret.</param>
        public StoredStateClient(AuthorizationServerDescription authorizationServer,
            String clientIdentifier,
            String clientSecret,
            IAuthorizationState state)
            : base(authorizationServer, clientIdentifier, clientSecret)
        {
            this.State = state;
        }

        public IAuthorizationState State { get; private set; }

        /// <summary>
        /// Returns the IAuthorizationState stored in the StoredStateClient instance.
        /// </summary>
        /// <param name="provider">OAuth2 client.</param>
        /// <returns>The stored authorization state.</returns>
        static public IAuthorizationState GetState(StoredStateClient provider)
        {
            return provider.State;
        }
    }
}
