# KVV-PHP (unofficial)

## Description

This is an unofficial PHP library for the KVV public transport association in Germany (Baden-Württemberg).
It is not developed as part of the official KVV information system and therefore is subject to change without notice.

The library consists of two main modules. The `\KVV\EFA` and the `\KVV\Live\EFA` endpoint.
Both of them allow you to request different data and do different operations.
The `\KVV\Live\EFA` is signifikantly faster than the other one, because it does not need to parse HTML code and consequently is also more stable.
The following is a feature list, showing what you can do with this library.

### Basic \KVV\EFA API
* search for bus/tram stations and place names via [autocomplete api](https://github.com/MartinLoeper/KVV-PHP-unofficial-/wiki/Basic-Usage#autocomplete)
* search for trips between two locations via [search api](https://github.com/MartinLoeper/KVV-PHP-unofficial-/wiki/Basic-Usage#search)
* search for trips with special attributes such as low-floor vehicles via [advanced search api](https://github.com/MartinLoeper/KVV-PHP-unofficial-/wiki/Advanced-Usage#search-optimization)
* paging between trips via [paging module](https://github.com/MartinLoeper/KVV-PHP-unofficial-/wiki/Advanced-Usage#paging-recommended-implementation)

### Fast \KVV\Live\EFA API
* [search stops by name](https://github.com/MartinLoeper/KVV-PHP-unofficial-/wiki/Live-API#search-stop-by-name)
* [search stops by id](https://github.com/MartinLoeper/KVV-PHP-unofficial-/wiki/Live-API#search-stop-by-id)
* [search stops by latitude and longitude](https://github.com/MartinLoeper/KVV-PHP-unofficial-/wiki/Live-API#search-stop-by-latitudelongitude)
* [request live departures from tram stops](https://github.com/MartinLoeper/KVV-PHP-unofficial-/wiki/Live-API#grab-live-departures-by-stop-id)
* [request live departures by stop and route](https://github.com/MartinLoeper/KVV-PHP-unofficial-/wiki/Live-API#grab-live-departures-by-stop-id-and-route)
* [determine if a given stop is part of a route](https://github.com/MartinLoeper/KVV-PHP-unofficial-/wiki/Live-API#check-if-stop-belongs-to-route)

For a full documentation and some useful examples visit the wiki: https://github.com/MartinLoeper/KVV-PHP-unofficial-/wiki

### Other languages
Bindings for other programming languages are available for:
* <a href="https://github.com/Nervengift/kvvliveapi">Python (Live API)</a>

<blockquote>
If you want to look into the reference, visit the <a href='https://github.com/MartinLoeper/KVV-PHP-unofficial-/wiki/Types'>Types Wiki-Page</a>.
</blockquote>