### Generate secret key

I have included a helper command to generate a key for you:

```bash
php artisan jwt:secret
```

This will update your `.env` file with something like `JWT_SECRET=foobar`

It is the key that will be used to sign your tokens. How that happens exactly will depend
on the algorithm that you choose to use.

### Generate certificate

For generating certificates the command 

```bash
php artisan jwt:generate-certs
```

can be used. The `.env` file will be updated, to use the newly created certificates. 

The command accepts for following paramters

| name | description |
|---|---|
| force | override existing certificates |
| algo | Either rsa or ec |
| bits | Key length for rsa |
| curve | Curve to be used for ec |
| sha | Hashing algorithm |
| passphrase | Passphrase for the cert |
| ask-passphrase | Ask for passphrase instead of passing as parameter |
| dir | Folder to place the certificates |

#### Examples 

Generating a 4096 bit rsa certificate with sha 512

```bash
php artisan jwt:generate-certs --force --algo=rsa --bits=4096 --sha=512
```

Generating a ec certificate with prime256v1-curve and sha 512

```bash
php artisan jwt:generate-certs --force --algo=ec --curve=prime256v1 --sha=512
```