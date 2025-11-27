![Enum Helper-Dark](branding/dark.png#gh-dark-mode-only)![Enum Helper-Light](branding/light.png#gh-light-mode-only)
# CARTA del DOCENTE

[![Latest Version on Packagist](https://img.shields.io/packagist/v/datomatic/carta-del-docente.svg?style=for-the-badge)](https://packagist.org/packages/datomatic/carta-del-docente)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/datomatic/carta-del-docente/php-cs-fixer.yml?label=code%20style&color=5FE8B3&style=for-the-badge)](https://github.com/datomatic/carta-del-docente/actions/workflows/php-cs-fixer.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/datomatic/carta-del-docente.svg?style=for-the-badge)](https://packagist.org/packages/datomatic/carta-del-docente)

Il pacchetto non ufficiale per integrare facilmente la Carta del Docente in un progetto PHP.

Se utilizzate il framework Laravel ho creato anche un pacchetto ad hoc [datomatic/laravel-carta-del-docente](https://github.com/datomatic/laravel-carta-del-docente).

## Requisiti
- PHP >= 8.0
- ext-soap

## Installazione

Puoi installare il pacchetto via composer:

```bash
composer require datomatic/carta-del-docente
```

## Configurazione

Per poter utilizzare il pacchetto bisogna leggere la [documentazione ufficiale](https://test-carta-docente.readthedocs.io/it/latest/linee-guida-esercenti.html) che spiega tutto il processo della Carta del Docente.

Per la parte di test è già tutto pronto senza dover generare nessun certificato (l'ho già fatto io per voi); basta solo richiedere dei voucher di test tramite email a [docenti@sogei.it](mailto:docenti@sogei.it).

Per la parte di produzione, invece, va generato un certificato seguendo la [guida](#come-generare-un-certificato-valido). 

## Utilizzo

Per prima cosa bisogna inizializzare il client con i dati relativi al certificato e la password del certificato.

Per l'ambiente di test il certificato e la password sono già inclusi nel pacchetto (essendo uguali per tutti). 
```php
//Test
$testClient = new Datomatic\CartaDelDocente\CartaDelDocenteClient();
```

Per l'ambiente di produzione è necessario fornire il path al certificato (possibilmente non mettetelo accessibile esternamente) e la relativa password.

```php
//Produzione
$client = new Datomatic\CartaDelDocente\CartaDelDocenteClient('../path/certificate.pem', 'passwordCertificato');
```

Una volta ottenuto il client è possibile utilizzare le poche funzionalità necessarie.

### Check

Per effettuare il Check del voucher basta chiamare la funzione `check` passando il tipo di operazione (1,2,3) e il codice del voucher.

```php
$result = $client->check(1, 'voucher');
```

La funzione ritorna un oggetto `CartaDelDocenteResponse` in caso di esito positivo oppure una eccezione `RequestException` in caso di errore.

L'oggetto `CartaDelDocenteResponse` è composto dalle seguenti proprietà:

```php
$result->name // CF o nominativo del beneficiario
$result->vatId // partita IVA esercente
$result->scope // l'ambito del voucher (cinema, teatro, libreria,...)
$result->good // il tipo di bene (libri, spettacoli,...)
$result->amount // importo totale del buono
```

### Confirm

Per effettuare il Confirm del voucher basta chiamare la funzione `confirm` passando il tipo di operazione (1), il codice del voucher e l'importo da confermare.

```php
$result = $client->confirm(1, 'Codice Voucher', 52.50);
```


### Attivazione esercente

Questa operazione va fatta solo una volta all'inizio ed è disponibile solo per la produzione in quanto in ambiente di test non è necessaria.

```php
$client->merchantActivation();
```
La funzione ritorna un oggetto `CartaDelDocenteResponse` oppure una eccezione `RequestException` in caso di errore.
Se tutto è andato a buon fine verrà ritornato il seguente oggetto:

```php
Datomatic\CartaDelDocente\CartaDelDocenteResponse {
  +name: "Attivazione effettuata"
  +vatId: "12345678901" // p.IVA esercente attivato
  +scope: "-"
  +good: "-"
  +amount: 0
}
```

## Come generare un certificato valido

Per generare correttamente un certificato è necessario eseguire delle operazioni da terminale Linux / Mac OS oppure WSL di Windows.

NB:Se ottenete un errore di comando non valido potrebbe essere necessario scrivere a mano il comando intero invece che fare copia incolla.

Per prima cosa dobbiamo andare a creare la richiesta di certificato da usare sul sito [cartadeldocente](https://www.cartadeldocente.istruzione.it/docentiEsercente/#/login).

Per fare questo prima creiamo il file req.cer da caricare nel sito:

```bash
openssl req -newkey rsa:2048 -keyout key.der -out req.der -outform DER
```

Una volta premuto invio dovremmo per prima cosa inserire una password (PEM pass) due volte, e Poi andare a compilare i seguenti campi:

- Country Name (2 letter code) [AU]: // la nazione in due cifre es: IT
- State or Province Name (full name) [Some-State]: // la provincia es: Vicenza
- Locality Name (eg, city) []: // la città es: Valdagno
- Organization Name (eg, company) [Internet Widgits Pty Ltd]: // l'organizzazione es: Acme srl
- Organizational Unit Name (eg, section) []: // sezione dell'organizzazione es: Shop
- Common Name (e.g. server FQDN or YOUR name) []: // nome del richiedente es: Mario Rossi
- Email Address []: // indirizzo email
- A challenge password []: // una nuova password che per comodità metteremo uguale a quella già inserita

Ottenuto il file req.der lo possiamo caricare sul sito [cartadeldocente](https://www.cartadeldocente.istruzione.it/CommercianteWeb/#/login) nella form di richiesta certificato ed dopo qualche secondo/minuto possiamo scaricare il file `[PIVA-azienda].cer` che andremmo a mettere nella stessa cartella dove abbiamo gli altri file sopra creati.

Dopo aver avuto il file .cer lo andiamo a convertire in .pem (i files per comodità li chiamo XXXXX ma voi avrete il numero di partita iva).

```bash
openssl x509 -inform DER -in XXXXX.cer -out XXXXX.pem
```

Poi lo convertiamo in un file .p12 combinandolo con la chiave generata nel primo passaggio:

```bash
openssl pkcs12 -export -inkey key.der -in XXXXX.pem  -out XXXXX.p12
```

Infine convertiamo il certificato .p12 nel certificato `result.pem` finale da usare in produzione

```bash
openssl pkcs12 -in 02017240249.p12 -out result.pem -clcerts
```

Quindi per usare il pacchetto in produzione bisognerà mettere il path al file `result.pem`.


### N.B.: ricordatevi che il certificato ha valenza 3 anni e quindi andrà rigenerato ogni 3 anni
Per vedere la scadenza del certificato eseguite il seguente comando:
```bash
openssl x509 -enddate -noout -in result.pem
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Alberto Peripolli](https://github.com/trippo)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
