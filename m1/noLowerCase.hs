noLowercaseStrings :: [String] -> [String]

containsLowercase :: String -> Bool

-- använder any för att se ifall lowercase finns i strängen. any tar en funktion och en lista och returnerar True om funktionen är True för något element i listan.
containsLowercase str = any (\x -> elem x ['a'..'z']) str

-- använder filter för att filtrera bort alla strängar som innehåller lowercase. 
-- filter tar en funktion och en lista och returnerar en lista med alla element som funktionen är True för.
noLowercaseStrings v = filter (not . containsLowercase) v

-- Testad och klar!