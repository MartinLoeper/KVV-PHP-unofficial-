KVV-PHP (unofficial)

This is an unofficial PHP library for the KVV public transport association in Germany (Baden-Württemberg).
It is not developed as part of the official KVV information system and therefore is subject to change without notice.
The Live-Endpoint provides access to a REST-Webservice and thus is very performant, whereas the other standard endpoint parses HTML-code and is really slow. Unfortunately there are many data which cannot be extracted from the Live-Endpoint, so a good caching mechanism is recommended if using the latter one.
