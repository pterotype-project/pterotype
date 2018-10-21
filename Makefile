dist/pterotype.zip: vendor clean_zip
	mkdir -p dist && zip -r dist/pterotype.zip . -x \.git/\* dist/\* log/\* svn/\*

clean_zip:
	rm -f dist/pterotype.zip

svn: README.txt assets composer.json composer.lock includes pterotype.php vendor
	mkdir -p svn
	rsync -av assets svn
	rsync -av README.txt composer.json composer.lock includes pterotype.php vendor svn/trunk

vendor:
	composer install
