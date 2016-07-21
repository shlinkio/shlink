
# REST API documentation

## Error management

Statuses:

* 400 -> controlled error
* 401 -> authentication error
* 500 -> unexpected error

[TODO]

## Authentication

[TODO]

## Endpoints

#### Authenticate

**REQUEST**

* `POST` -> `/rest/authenticate`
* Params:
    * username: `string`
    * password: `string`

**SUCCESS RESPONSE**

```json
{
    "token": "9f741eb0-33d7-4c56-b8f7-3719e9929946"
}
```

**ERROR RESPONSE**

```json
{
    "error": "INVALID_ARGUMENT",
    "message": "You have to provide both \"username\" and \"password\""
}
```

Posible errors:

* **INVALID_ARGUMENT**: Username or password were not provided. 
* **INVALID_CREDENTIALS**: Username or password are incorrect. 


#### Create shortcode

**REQUEST**

* `POST` -> `/rest/short-codes`
* Params:
    * longUrl: `string` -> The URL to shorten
* Headers:
    * X-Auth-Token: `string` -> The token provided in the authentication request
    
**SUCCESS RESPONSE**

```json
{
    "longUrl": "https://www.facebook.com/something/something",
    "shortUrl": "https://doma.in/rY9Kr",
    "shortCode": "rY9Kr"
}
```

**ERROR RESPONSE**

```json
{
    "error": "INVALID_URL",
    "message": "Provided URL \"wfwef\" is invalid. Try with a different one."
}
```

Posible errors:

* **INVALID_ARGUMENT**: The longUrl was not provided. 
* **INVALID_URL**: Provided longUrl has an invalid format or does not resolve. 
* **UNKNOWN_ERROR**: Something unexpected happened. 


#### Resolve URL

**REQUEST**

* `GET` -> `/rest/short-codes/{shortCode}`
* Route params:
    * shortCode: `string` -> The short code we want to resolve
* Headers:
    * X-Auth-Token: `string` -> The token provided in the authentication request
    
**SUCCESS RESPONSE**

```json
{
    "longUrl": "https://www.facebook.com/something/something"
}
```

**ERROR RESPONSE**

```json
{
    "error": "INVALID_SHORTCODE",
    "message": "Provided short code \"abc123\" has an invalid format"
}
```

Posible errors:

* **INVALID_ARGUMENT**: No longUrl was found for provided shortCode. 
* **INVALID_SHORTCODE**: Provided shortCode does not match the character set used by the app to generate short codes. 
* **UNKNOWN_ERROR**: Something unexpected happened. 


#### List shortened URLs

**REQUEST**

* `GET` -> `/rest/short-codes`
* Query params:
    * page: `integer` -> The page to list. Defaults to 1 if not provided.
* Headers:
    * X-Auth-Token: `string` -> The token provided in the authentication request
    
**SUCCESS RESPONSE**

```json
{
    "shortUrls": {
        "data": [
            {
                "shortCode": "abc123",
                "originalUrl": "http://www.alejandrocelaya.com",
                "dateCreated": "2016-04-30T18:01:47+0200",
                "visitsCount": 4
            },
            {
                "shortCode": "def456",
                "originalUrl": "http://www.alejandrocelaya.com/en",
                "dateCreated": "2016-04-30T18:03:43+0200",
                "visitsCount": 0
            },
            {
                "shortCode": "ghi789",
                "originalUrl": "http://www.alejandrocelaya.com/es",
                "dateCreated": "2016-04-30T18:10:38+0200",
                "visitsCount": 0
            },
            {
                "shortCode": "jkl987",
                "originalUrl": "http://www.alejandrocelaya.com/es/",
                "dateCreated": "2016-04-30T18:10:57+0200",
                "visitsCount": 0
            },
            {
                "shortCode": "mno654",
                "originalUrl": "http://blog.alejandrocelaya.com/2016/04/09/improving-zend-service-manager-workflow-with-annotations/",
                "dateCreated": "2016-04-30T19:21:05+0200",
                "visitsCount": 1
            },
            {
                "shortCode": "pqr321",
                "originalUrl": "http://www.google.com",
                "dateCreated": "2016-05-01T11:19:53+0200",
                "visitsCount": 0
            },
            {
                "shortCode": "stv159",
                "originalUrl": "http://www.acelaya.com",
                "dateCreated": "2016-06-12T17:49:21+0200",
                "visitsCount": 0
            },
            {
                "shortCode": "wxy753",
                "originalUrl": "http://www.atomic-reader.com",
                "dateCreated": "2016-06-12T17:50:27+0200",
                "visitsCount": 0
            },
            {
                "shortCode": "zab852",
                "originalUrl": "http://foo.com",
                "dateCreated": "2016-07-03T09:07:36+0200",
                "visitsCount": 0
            },
            {
                "shortCode": "cde963",
                "originalUrl": "https://www.facebook.com.com",
                "dateCreated": "2016-07-03T09:12:35+0200",
                "visitsCount": 0
            }
        ],
        "pagination": {
            "currentPage": 4,
            "pagesCount": 15
        }
    }
}
```

**ERROR RESPONSE**

```json
{
    "error": "UNKNOWN_ERROR",
    "message": "Unexpected error occured"
}
```

Posible errors:

* **UNKNOWN_ERROR**: Something unexpected happened. 


#### Get visits

**REQUEST**

* `GET` -> `/rest/short-codes/{shortCode}/visits`
* Route params:
    * shortCode: `string` -> The shortCode from which we eant to get the visits.
* Query params:
    * startDate: `string` -> If provided, only visits older that this date will be returned
    * endDate: `string` -> If provided, only visits newer that this date will be returned
* Headers:
    * X-Auth-Token: `string` -> The token provided in the authentication request
    
**SUCCESS RESPONSE**

```json
{
    "shortUrls": {
        "data": [
            {
                "referer": null,
                "date": "2016-06-18T09:32:22+0200",
                "remoteAddr": "127.0.0.1",
                "userAgent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.87 Safari/537.36"
            },
            {
                "referer": null,
                "date": "2016-04-30T19:20:06+0200",
                "remoteAddr": "127.0.0.1",
                "userAgent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.87 Safari/537.36"
            },
            {
                "referer": "google.com",
                "date": "2016-04-30T19:19:57+0200",
                "remoteAddr": "1.2.3.4",
                "userAgent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.87 Safari/537.36"
            },
            {
                "referer": null,
                "date": "2016-04-30T19:17:35+0200",
                "remoteAddr": "127.0.0.1",
                "userAgent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.87 Safari/537.36"
            }
        ],
    }
}
```

**ERROR RESPONSE**

```json
{
    "error": "INVALID_ARGUMENT",
    "message": "Provided short code \"abc123\" is invalid"
}
```

Posible errors:

* **INVALID_ARGUMENT**: The shortcode does not belong to any short URL
* **UNKNOWN_ERROR**: Something unexpected happened.
