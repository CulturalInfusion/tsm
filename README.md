# Teacher's students management

## Installation

MySQL must be upper than 5.7.8 (or support JSON).
## Usage

- To dynamically get `max_allowance` of each level you should add it in the description of the level like this:

```html
<!--max_allowance=80-->
```

and instead of `80` you can set any number.

- If you're using tab, add `students` id to the one containing `tsm` shortcode.

- To use user API, call endpoint from this URI using GET method:

`URL/wp-content/plugins/tsm/api/user.php?username=USERNAME`

- To use import from `Google Classroom` feature:

From `https://developers.google.com/classroom/quickstart/php` Enable Classroom API and then set OAuth credentials, set `Web Server` and callback `URL/PAGE?page=tsm-front&task=set-google-auth-code` (in addition to `URL`, replace `PAGE` with path to the page which holds tsm shortcode like `teachers-dashboard`) then download `credentials.js` and copy content of it as JSON string into the settings of plugin. **Please note if it asks for verification proceed with [link](https://console.cloud.google.com/apis/credentials/consent)**