noLowercaseStrings :: [String] -> [String]

containsLowercase :: String -> Bool
containsLowercase str = any (`elem` ['a'..'z']) str

noLowercaseStrings v = filter (not . containsLowercase) v


-- Testad och klar!