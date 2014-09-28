<h1>KVV-PHP (unofficial)</h1>

<h2>Description</h2>

This is an unofficial PHP library for the KVV public transport association in Germany (Baden-Württemberg).
It is not developed as part of the official KVV information system and therefore is subject to change without notice.
The Live-Endpoint provides access to a RESTful web-service and thus is very performant, whereas the other standard endpoint parses HTML-code and is really slow. Unfortunately there is many information which cannot be extracted from the Live-Endpoint, so a good caching mechanism is recommended if using the latter one.
<br/><hr>
For a full documentation and some useful examples visit the wiki: https://github.com/MartinLoeper/KVV-PHP-unofficial-/wiki