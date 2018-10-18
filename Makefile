dist/pterotype.zip: clean
	composer install && mkdir -p dist && zip -r dist/pterotype.zip . -x \.git/\* dist/\* log/\*

clean:
	rm -f dist/pterotype.zip
