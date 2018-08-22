# Form
This plugin validates submitted form data and passes them to one or more handlers for processing.

In order to provide some CSRF protection and to prevent spam this middleware sets a cookie containing a unique token. Upon form submission this token is compared with token obtained from the form data and the values must match. The CSRF form field has to be manually added to each form and it must be populated.

To allow tracking of submitted forms through Google Analytics or similar services special query parameter `formSent` is added to the page url after a successful form submission. Its values is the name of the form. For example, successfully submitting form named `constact` on page `www.example.com/contact` will redirect to page `www.example.com/contactformSent=contact`.

The localization of the form success flash message is done by setting the translation string in the language `*.yml` files same as any other translation. The validation error messages for the form fields and the error flash message for the form as a whole are always shown in the correct language without any further settings.

## Options
Options is a map where the key is the name of the form and the value is the map of options for the given form.

option                  | type         | required | description
------------------------|--------------|----------|------------
fields                  | map          | ❌        | A map of the fields in the given form. Each field is a map where the validator options are defined. See Fields for details.
handlers                | list of maps | ✓        | A list of maps where each map specifies a handler to process the submitted data. Every map must define the type option that specifies which handler to use. Other options depend on the given handler type. Handlers are processed sequentially in the order defined. See Handlers for details.
anchor                  | string       | ❌        | Name of an anchor to which the form scrolls after submission.
success_flash_message   | string       | ❌        | The message that is displayed after the form is successfully submitted. If the site is multi language and translation is set, it will be translated automatically.

### Fields
Validation rules are defined as a list of constraints. [See the documentation](http://symfony.com/doc/current/validation.html#properties "Validating properties") for validation of properties in Symfony.
This validator does not support any data classes, it supports only validation of single properties (see an example below).

**Example**
```yaml
# site.yml

form:
    contact:
        anchor: contact-form
        handlers:
            # ...
        fields:
            name:
                - NotBlank: ~
                - Length:
                    min: 3
            email:
                - Email:
                    checkMX: true
```

### Handlers
#### API
Sends request with form data to the specified API endpoint. **If `response.redirect_url_param` is set, API handler must be the last handler in a queue because it immediately redirects to received URL.**

option      | type      | required   | description
------------|-----------|------------|------------
url         | string    | ✓          | An url to which request is send.
method      | string    | ✓          | A method using which request is send.
data        | map       | ✓          | Keys in the map determine attributes that are send in a request, for example *firstName*, *lastName*, *email* and so on. Values of those keys accept string or map. If it is string or integer, value is directly mapped to the attribute. If it is the map, required `form_value` and optional `default` both accept string or integer. `form_value` maps form field to the attribute that is send in the request and `default` is used, if form field is not filled in. By default, everything is send as string but you can use `format` that accepts `string` (default), `int`, `float` and `bool` that converts it to the entered data type. Special format `datetime` is supported, but you have to use object with `type`, `in` and `out` attributes instead of string representation (`format: datetime`). `type` accepts same values as `format`, `in` accepts PHP datetime format of form field value and `out` accepts PHP datetime format of value which is send over API.
headers     | map       | ❌         | Keys in the map determine request headers and its string or integer values.
response    | map       | ❌         | Accept only `redirect_url_param` that can be string or integer. `redirect_url_param` determines attribute in a response object that contains URL and should be used for redirection.

#### Email
Sends the form data to the specified email addresses. Note that in order to use this handler, the [mailer component](/reference/components/mailer) must be configured.

option      | type      | required   | description
------------|-----------|------------|------------    
recipients  | map       | ✓          | A map with keys `to` and `bcc`. Each key holds a list of zero or more email addresses. Value submitted by user can be used by using the placeholder `{{form_field_name}}`. It is useful, for example, to send an email to the user who submitted the form. All placeholders must be quoted.
from        | map       | ✓          | The sender as it should appear in the from email header. It must have the following keys: `name`, `address`
templates   | map       | ✓          | A map with exactly two keys: `subject`, `body`. Each holds a string with a path to the template for the respective part of the email message. The path is first checked relative to `<site_root>src/templates/<locale>` folder. If no match is found, the path is checked relative to `<site_root>src/templates` folder. Within both templates are available the same variables as in non-email templates including `form.<form_name>.data` populated with user submitted form data. 

#### Log
Logs the form data to the specified log file.

option      | type      | required   | description
------------|-----------|------------|------------
file        | string    | ✓          | Name of the file to which the data is written. The log file is located in <site_root>/var/log

**Example**
```yaml
# site.yml

form:
    contact:
        anchor: contact-form
        handlers:
            -   type: email
                from:
                    name: John Doe Corp.
                    address: john@doe.com
                recipients:
                    to: [ example_1@example.com, "{{email}}" ]
                    bcc: [ example_2@example.com ]
                templates:
                    subject: email/subject.html.twig
                    body: email/body.html.twig
            -   type: log
                file: form.log
            -   type: api
                url: 'https://api.example.com/users'
                method: POST
                data:
                    name:
                        form_value: name
                        format: string # Default format
                    date:
                        form_value: date
                        format:
                            type: datetime # Format
                            in: 'd.m.Y H:i' # Input format, e.g. datetime value in form field
                            out: 'Y-m-d H:i:s' # Output format, e.g. datetime send over API
                    email:
                        form_value: email
                        default: 'anonymous@example.com' # Value send when email is not filled
                    formToken: notSoSecretFormToken
                headers:
                    apiToken: topSecretApiToken
                # response:
                #    redirect_url_param: redirectToUrlParam # Reads `redirectToUrlParam` attribute from response object and redirect to it
```
```html
    {# index.html.twig #}
    
    <div>

        {% if form.contact.flash_message %}
            <div class="alert--{{ form.contact.flash_message_type }}">
                {{ form.contact.flash_message }}
            </div>
        {%  endif %}

        <form method="post" action="#{{ form.contact.anchor }}">
            <input type="hidden" name="contact[csrf_token]" value="{{ form.contact.data.csrf_token }}" />

            <input type="text" name="contact[name]" value="{{ form.contact.data.name }}" />
            {% if form.contact.errors.name|length > 0 %}
                <ul>
                    {% for error in form.contact.errors.name %}
                        <li>{{ error }}</li>
                    {% endfor %}
                </ul>
            {% endif %}

            <textarea rows="5" cols="1" name="contact[message]">{{ form.contact.data.message }}</textarea>
            {% if form.contact.errors.message|length > 0 %}
                <ul>
                    {% for error in form.contact.errors.message %}
                        <li>{{ error }}</li>
                    {% endfor %}
                </ul>
            {% endif %}

            <input type="submit" value="Submit" />
        </form>

    </div>
```

## Provides
Provides a map named `form` where each key is the name of the form and the value is a map with the following values.

name               | type   | description
-------------------|--------|-------------
anchor             | string | Name of anchor to which the form should scroll after submit. Must be used in <form> tag action attribute to enable scroll after submitting invalid data.
csrf_token         | string | The csrf token to be used in validation.
data               | map    | The user submitted data. Must be manually rendered in template to persist invalid data.
errors             | map    | A map of lists where each map key is a field name and each element in the list is one error message. There is one error message for each failed validator constraint.
flash_message      | string | The message informing the user of the result of the action he or she took. For example it informs the user weather the form was submitted successfully or if there are any invalid fields.
flash_message_type | enum   | The type indicating whether a flash message is of type `success`, `error-validation` or  `error-expired-token`.
