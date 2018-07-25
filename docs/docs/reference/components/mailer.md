# Mailer
**[config version: 2+]**

This component is used to send emails from the system.

Since committing the smtp server credentials into VCS is generally not a good idea, it is recommended to use [parameter substitution](/core_concepts/#substitution).

## Options

option                  | type         | required | description
------------------------|--------------|----------|------------
smtp_host               | string       | ✓        | The hostname of the smtp server
smtp_port               | string       | ✓        | The port of the smtp server
smtp_encryption         | string       | ❌       | Type of encryption. Valid types are `ssl` or `tls`
smtp_username           | string       | ❌       | Username used to authenticate
smtp_password           | string       | ❌       | Password used to authenticate

**Example**
```
# site.yml
version:2 

components:
    mailer:
        smtp_host: smtp.example.com
        smtp_port: 587
        smtp_encryption: tls
        
        # username and password are loaded from the parameters.yml file
        smtp_username: "%username%"
        smtp_password: "%username%"
```
