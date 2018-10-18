dist/pterotype.zip:
	mkdir -p dist && zip -r dist/pterotype.zip . -x \.git/\* dist/\* log/\*
