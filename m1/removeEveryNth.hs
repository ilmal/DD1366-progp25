-- Definierar en funktion removeEveryNth som tar ett heltal n och en lista av heltal.
removeEveryNth :: Int -> [Int] -> [Int]
-- Anropar en hjälpfunktion "helper" med startvärden:
-- list - den ursprungliga listan,
-- 1 - vi börjar räkna från 1,
-- [] - en tom ackumulator för de behållna elementen,
-- n - varje n:te element ska tas bort.
removeEveryNth n list = (helper list 1 [] n)
  where
    -- Hjälpfunktionen tar en lista av Int, ett räknare värde (Int), en ackumulator (lista av Int)
    -- och n (Int), samt returnerar en lista av Int.
    helper :: [Int] -> Int -> [Int] -> Int -> [Int]    
    -- Rekursivt fall: Hanterar listans första element x och resten list.
    helper (x:list) count acc n
      -- Om räknaren är lika med n, hoppa över elementet x och återställ räknaren till 1.
      | count == n  = helper list 1 acc n
      -- Annars, inkludera elementet x i ackumulatorn och öka räknaren.
      | otherwise   = helper list (count + 1) (x:acc) n

    -- Basfallet: Om listan är tom, returnera ackumulatorn.
    helper [] _ acc _ = reverse acc


-- Testad och klar!