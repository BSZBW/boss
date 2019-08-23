# BSZ Custom Styles

## Ordner `bsz`

In diesem Ordner liegen LESS-Dateien für unterschiedliche Bereiche von VuFind. 
Bevor man etwas hinzufügt, sollte man sich überlegen in welche Datei es gut passt. 
`modifications.less` fasst all diese Dateien zusammen. 

## Ordner `bsz/custom`

Hier kommen alle **sichtspezifischen Anpassungen** rein, aber nichts, was für alle 
Sichten gilt. Oft braucht man aber gar keine Styles sondern nur Variablen. 


## Ordner `bsz/variables`

Hier kommen die Variablendateien für die einzelnen Sichten rein. Viele Stylings 
lassen sich mit nur Variablen umsetzen. 

## Ordner `components`

Hier drin liegen Dateien, die zu Bootstrap gehören, aber von uns angepasst werden
mussten. Sie werden wie alle anderen Bootstrap-Dateien in `vufind.less` inkludiert.

Alle Bootstrap-Dateien, die wir nicht angefasst haben, liegen im Theme 
`bootstrap3`. 

## Ordner `vendor`

In diesem Ordner liegen hauptsächlich fertige Styles, z.B. von jQuery-Plugins. 
Sie werden alle zusammen in `vendor/vendor.less` inkludiert. Außerdem liegen hier
die alten Themes von Bootswatch Die meisten unserer Sichten benutzen `united`. 

In dem Ordner liegen auch Dateien, die **Schriften** definieren. 

## Makefile

Um die Generierung der CSS-Dateien zu vereinfachen, gibt es eine Makefile. Sie 
hat folgende Modi:

* `make all` generiert alle CSS Dateien, wo sich etwas geändert hat. Das dauert. 
* `make clean` löscht die alten Dateien und generiert alle neu. 
* `make css/hbgo.css` generiert geau diese eine Datei neu. 

