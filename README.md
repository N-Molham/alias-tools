# Alias Tools
Personal tools to speed up the developing process

## Usage

#### Package WordPress plugin or theme 
`wppackage [dir-name] [version="auto-detect"]`

ex:
`wppackage my-plugin`
Generates a zip file names `my-plugin-ver-1.0.zip` with version number detected automatically.

`wppackage my-plugin 1.2`
Generates a zip file names `my-plugin-ver-1.2.zip` with version number forced.

#### Create/Update WordPress installation
`wpnew [new|update] [dir-name] [dbname] [dbuser] [dbpass=""] [dbhost="localhost"] [dbprefix="wp_"] [dbcharset="utf8"] [dbcollate=""]  [locale=""]`

ex:
`wpnew new testwp testdb root pass`
Creates new WordPress installation named `testwp` contected to database named `testdb` with username of `root` and password of `pass`.

`wpnew update store shop shop-user shop-pass 127.0.0.1` 
Updates existing WordPress installation named `store` database connection to `shop` hosted in IP `127.0.0.1` with username of `shop-root` and password of `shop-pass`

## Notes

- Arguments passed without the `[]` characters.
- Arguments that optional, have a default value like `[dbhost="localhost"]` other than that, it's a required argument.
- These tools are customized for personal needs and may not suite your needs 100%, but I am open for suggestions, just ping me or open a issue.

## License
The MIT License (MIT)