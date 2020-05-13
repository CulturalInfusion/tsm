# Teacher's students management

## Usage

- To dynamically get `max_allowance` of each level you should add it in the description of the level like this:
```html
<!--max_allowance=80-->
```
and instead of `80` you can set any number.


- To use user API, call endpoint from this URI using GET method:

`URL/wp-content/plugins/tsm/api/user.php?username=USERNAME`

- To use import from `Google Classroom` feature:

From `https://developers.google.com/classroom/quickstart/php` Enable Classroom API and then set `Web Server` and callback
`URL/PAGE?page=tsm-front&task=set-google-auth-code` (in addition to `URL`, replace `PAGE` with path to the page which holds tsm shortcode like `teachers-dashboard`) then download `credentials.js` and copy content of it as JSON string, then copy `credentials.php.example` to `credentials.php` at the root of plugin and paste the JSON string in there.