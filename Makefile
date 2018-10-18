dist/pterotype.zip: clean
	mkdir -p dist && zip -r dist/pterotype.zip . -x \.git/\* dist/\* log/\*

clean:
	rm dist/pterotype.zip
