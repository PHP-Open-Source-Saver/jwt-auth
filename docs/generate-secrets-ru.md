### Сгенерируйте секретный ключ

Воспользуйтесь вспомогательной командой:

```bash
php artisan jwt:secret
```

В вашем `.env` файле должно появиться что-то похожее на: `JWT_SECRET=foobar`

Этот ключ будет использован для подписи ваших токенов. Как именно это будет происходить зависит от выбранного вами алгоритма.

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
