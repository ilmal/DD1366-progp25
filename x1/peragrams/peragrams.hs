import Data.List(group, sort)

-- helper func to find amount of odd freq letters
countOddFreq :: [Int] -> Int
countOddFreq freqs = length (filter odd freqs) -- counts how many letters that are odd freq

-- Func that finds min nr of times to remove letters to become peragram
-- If 1 letters are oddfreq: 1-1 = 0 -> max 0 0 = 0
-- If 0 letters are oddfreq: 0-1 = -1 -> max 0 (-1) = 0
-- If n letters are oddfreq: n-1 -> max 0 n-1 = n-1
--          n-1 because ONE letter may be oddfreq at most
countRemovedLetters :: String -> Int
countRemovedLetters inputString = max 0 (countOddFreq letterCount - 1)
    where 
        letterCount = map length (group (sort inputString))

-- Main func to take terminal input and evaluate string
main :: IO()
main = do
    inputString <- getLine
    let test = countRemovedLetters inputString
    print test