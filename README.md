![AWHS Logo](https://nicolasmeloni.ovh/images/awhspanel.png)

# UserBundle
UserBundle of AWHSPanel  
This bundle provides basic authentication.

## Requirements
Have installed the [foundation](https://github.com/TheGrimmChester/AWHSPanel/blob/master/README.md)

## Installation
1. Clone this bundle inside `src/AWHS/` directory.
2. Enable the bundle in the kernel by adding the following line right after `//AWHS Bundles` in `app/AppKernel.php` file:  
`new AWHS\UserBundle\AWHSUserBundle(),`
3. Append the following configuration to the `app/config.yml` file:  
```yaml
#UserBundle Configuration
fos_user:
    db_driver: orm # other valid values are 'mongodb', 'couchdb' and 'propel'
    firewall_name: main
    user_class: AWHS\UserBundle\Entity\User
    group:
        group_class: AWHS\UserBundle\Entity\Group
    profile:
        form:
            type: AWHS\UserBundle\Form\Type\ProfileFormType
    registration:
        form:
            type: AWHS\UserBundle\Form\Type\RegistrationFormType

```
4. Append the following routing to the `app/routing.yml` file:  
```yaml
#FOSUserBundle
fos_user:
    resource: "@FOSUserBundle/Resources/config/routing/all.xml"
fos_user_group:
    resource: "@FOSUserBundle/Resources/config/routing/group.xml"
    prefix: /group
#UserBundle
awhs_user:
    resource: "@AWHSUserBundle/Resources/config/routing.yml"
    prefix:   /
```

5. Configure the `app/security.yml` file as follow:  
```yaml
# To get started with security, check out the documentation:
# http://symfony.com/doc/current/book/security.html
security:

    encoders:
        FOS\UserBundle\Model\UserInterface: bcrypt
    role_hierarchy:
        ROLE_ADMIN:       ROLE_USER
        ROLE_SUPER_ADMIN: ROLE_ADMIN
    # http://symfony.com/doc/current/book/security.html#where-do-users-come-from-user-providers
    providers:
        fos_userbundle:
            id: fos_user.user_provider.username

    firewalls:
        # disables authentication for assets and the profiler, adapt it according to your needs
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        main:
            pattern: ^/
            form_login:
                provider: fos_userbundle
                csrf_token_generator: security.csrf.token_manager
                # if you are using Symfony < 2.8, use the following config instead:
                # csrf_provider: form.csrf_provider

            logout:       true
            anonymous:    true

    access_control:
        - { path: ^/login$, role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/register, role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/resetting, role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/admin/, role: ROLE_ADMIN }
```
6. Update database & clear cache: `php bin/console doctrine:schema:update --force; php bin/console cache:clear; php bin/console cache:clear --env=prod`  
You may have to set permissions back to www-data `chown -R www-data:www-data /usr/local/awhspanel/panel/*`
7. Create your first user: `php bin/console fos:user:create yourUsername test@example.com yourPassword`
8. Give your user admin privilege : `php bin/console fos:user:promote yourUsername ROLE_ADMIN`

## TODO
- [ ] Multilingual
- [ ] Refactoring old code.