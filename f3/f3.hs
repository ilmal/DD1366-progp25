module Main where

import UI.HSCurses.Curses
import Control.Monad (when, void)
import Control.Concurrent (threadDelay)
import Foreign.C.Types (CInt)

--  --- Datatyper ---
-- derive från Eq för att kunna jämföra riktningar med == osv 
data Direction = U | D | L | R deriving (Eq)

-- nuvarande pos + riktning
data Player = Player { 
  name :: String,
  pos :: (Int, Int), 
  dir :: Direction 
  }

<<<<<<< HEAD

=======
>>>>>>> 26e33e3113aceb15c7eb4e02f166e377f0e46748
-- två spelare, väggar, spelplanens storlek och om spelet är över
data GameState = GameState
  { p1 :: Player
  , p2 :: Player
  , walls :: [(Int, Int)] -- lista av koordinater, fylls på med spelarnas positioner under spelet!
  , boardWidth  :: Int
  , boardHeight :: Int
  , gameOver    :: Bool
  , winner     :: Player
  }

-- startvärden
initialState :: GameState
initialState = GameState
  { p1 = Player "Emilie" (5,5) R -- startpos + start riktning
  , p2 = Player "Nils" (24,14) L -- startpos + start riktning
  , walls = [] -- inga väggar till att börja med
  , boardWidth  = 30 -- spelplanens storlek
  , boardHeight = 20
  , gameOver    = False 
  }

-- Hantera indata
handleInput :: Int -> GameState -> GameState
handleInput ch state = state { p1 = newP1, p2 = newP2 }
  where
    newP1 = case ch of
              259 -> (p1 state){ dir = U } -- pil upp
              258 -> (p1 state){ dir = D } -- pil ner
              260 -> (p1 state){ dir = L } -- pil vänster
              261 -> (p1 state){ dir = R } -- pil höger
              _   -> p1 state -- annars behåll riktning
    newP2 = case ch of
              119 -> (p2 state){ dir = U }  -- w
              115 -> (p2 state){ dir = D }  -- s
              97  -> (p2 state){ dir = L }  -- a
              100 -> (p2 state){ dir = R }  -- d
              _   -> p2 state

-- hjälp funktion för att beräkna ny pos
delta :: Direction -> (Int, Int)
delta U = (0, -1)
delta D = (0, 1)
delta L = (-1, 0)
delta R = (1, 0)

-- ännu en hjälp, addera två tuplar (vektorer ish!)
add :: (Int, Int) -> (Int, Int) -> (Int, Int)
add (x,y) (dx,dy) = (x+dx, y+dy)

-- kolla om en pos är utanför spelplanen, antingen under 0 eller större än spelplanens storlek
outOfBounds :: (Int,Int) -> Int -> Int -> Bool
outOfBounds (x,y) w h = x <= 0 || x >= (w-1) || y <= 0 || y >= (h-1)

-- checka ifall en spelare är utanför spelplanen eller kolliderar med en vägg
collision :: Player -> GameState -> Bool
collision player state =
  outOfBounds (pos player) (boardWidth state) (boardHeight state) -- hjälpfunktion ovan
  || (pos player) `elem` (walls state) -- kolla om spelaren är på en vägg

-- flytta spelarna, kolla om de kolliderar med varandra eller väggar, uppdatera väggar
movePlayers :: GameState -> GameState
movePlayers state
  | collision newP1 newState = state { gameOver = True, winner = p2 state } -- checka ifall spelet är över (kollision)
<<<<<<< HEAD
  | collision newP2 newState = state { gameOver = True, winner = p1 state }  
=======
  | collision newP2 newState = state { gameOver = True, winner = p1 state }
>>>>>>> 26e33e3113aceb15c7eb4e02f166e377f0e46748
  | otherwise = state { p1 = newP1, p2 = newP2, walls = newWalls }                   -- annars uppdatera spelplanen
  where
    newP1 = (p1 state){ pos = add (pos (p1 state)) (delta (dir (p1 state))) } -- flytta spelarna mha delta
    newP2 = (p2 state){ pos = add (pos (p2 state)) (delta (dir (p2 state))) }
    newWalls = walls state ++ [ pos (p1 state), pos (p2 state) ]              -- lägg till väggar
    newState = state { walls = newWalls }                                     -- uppdatera väggar

-- help funktion för att konvertera en char till en CInt
-- behövs för att använda waddch
charToCh :: Char -> ChType
charToCh c = fromIntegral (fromEnum c)

-- Rita spelplanen på ett givet fönster
render :: GameState -> Window -> IO ()
render state scr = do
  wclear scr -- rensar fönstret mellan varje spelcykel
  drawBorder state scr -- ritar spelplanens gränser
  mapM_ (drawWall scr) (walls state) -- ritar ut alla väggar
  drawPlayer scr (p1 state) -- ritar ut spelare 1
  drawPlayer scr (p2 state) -- ritar ut spelare 2 
  refresh -- uppdaterar terminalen med informationen från window (uppdaterar terminalen)

-- Rita en ram runt spelplanen, oxå MONADER!!!!! :D
drawBorder :: GameState -> Window -> IO ()
drawBorder state scr = do
<<<<<<< HEAD
  let w = boardWidth state -- hämta spelplanens storlek
      h = boardHeight state
-- mapM_ är en monadisk variant av map, dvs den tar en monadisk funktion, i detta fall en IO funktion, vi använder _ variant för att ignorera returvärdet
=======
  let w = boardWidth state -- hämta bredden och höjden från GameState
      h = boardHeight state
  -- när man ritar med hscurses så flyttar vi först cursorn (nuvarande position) till en viss plats,
  -- sedan skriver vi ut en karaktär på den koordinaten. osv.
  -- mapM_ är en monadisk variant av map, dvs den tar en monadisk funktion, i detta fall en IO funktion, vi använder _ variant för att ignorera returvärdet
>>>>>>> 26e33e3113aceb15c7eb4e02f166e377f0e46748
  mapM_ (\x -> do
           wMove scr 0 x -- flyttar cursorn till (0,x) koordinater
           -- void för att vi inte bryr oss om returvärdet
           -- waddch skriver ut en karaktär på den platsen (window add char)
           -- charToCh konverterar en Char till en ChType
           void (waddch scr (charToCh '#')) -- skriver ut en karaktär på den platsen (taket)
           wMove scr (h-1) x -- flyttar cursorn till (h-1,x) koordinater
           void (waddch scr (charToCh '#')) -- skriver ut en karaktär på den platsen (golvet)
        ) [0 .. w-1] -- gör detta för alla x-koordinater
  mapM_ (\y -> do
           wMove scr y 0 -- flyttar cursorn till (y,0) koordinater
           void (waddch scr (charToCh '#')) -- skriver ut en karaktär på den platsen (vänstra väggen)
           wMove scr y (w-1) -- flyttar cursorn till (y,w-1) koordinater
           void (waddch scr (charToCh '#')) -- skriver ut en karaktär på den platsen (högra väggen)
        ) [0 .. h-1] -- gör detta för alla y-koordinater

drawWall :: Window -> (Int,Int) -> IO ()
drawWall scr (x,y) = do
  wMove scr y x -- flyttar cursorn till (y,x) koordinater
  void (waddch scr (charToCh '#')) -- skriver ut en karaktär på den platsen (samma som i drawBorder)

dirToChar :: Direction -> Char
dirToChar U = '^' -- hämtar karaktär för en viss riktning (spelarens huvud)
dirToChar D = 'v'
dirToChar L = '<'
dirToChar R = '>' 

drawPlayer :: Window -> Player -> IO ()
<<<<<<< HEAD
drawPlayer scr (Player _ (x,y) d) = do
  wMove scr y x
  void $ waddch scr (charToCh (dirToChar d))
=======
drawPlayer scr (Player _ (x,y) d) = do          -- hämtar x,y och riktning från Player
  wMove scr y x                               -- flyttar cursorn till (y,x) koordinater
  void (waddch scr (charToCh (dirToChar d)))  -- skriver ut en karaktär på den platsen (spelarens huvud) (samma som i drawBorder)
>>>>>>> 26e33e3113aceb15c7eb4e02f166e377f0e46748

-- Spelloopen
gameLoop :: GameState -> Window -> IO ()
gameLoop state scr = do
  if gameOver state -- checka ifall spelet är slut
    then do
      mvWAddStr scr (boardHeight state) 0 ("Game Over! " ++ name (winner state) ++ " vann! Tryck på ESC för att starta om.")
<<<<<<< HEAD
      refresh
      waitForEsc scr
    else do
      ch <- getch
      let intCh = fromIntegral ch :: Int
      let state1 = if intCh == (-1) then state else handleInput intCh state
=======
      refresh -- skriv ut meddelandet ovan 
      waitForEsc scr -- vänta på att användaren klickar på esc
    else do 
      ch <- getch -- hämta användarens input 
      let intCh = fromIntegral ch :: Int -- skriv om ch till int
      -- checka ifall input är -1 (inget knappytryck) annars uppdatera beroende på knapptryck
      let state1 = if intCh == (-1) then state else handleInput intCh state 
      -- flytta spelarna
>>>>>>> 26e33e3113aceb15c7eb4e02f166e377f0e46748
      let state2 = movePlayers state1
      -- rita spelplan + spelare + väggar
      render state2 scr
      -- sov i 100 ms (spelets hastighet)
      threadDelay 100000  -- 100 ms
      -- kalla på sig själv för att fortsätta spelet
      gameLoop state2 scr
  where
    waitForEsc scr = do
<<<<<<< HEAD
      ch <- getch
      let intCh = fromIntegral ch :: Int
      if intCh == 27  -- ESC key
        then do
          render initialState scr
          gameLoop initialState scr
        else waitForEsc scr
=======
      ch <- getch -- hämta input
      let intCh = fromIntegral ch :: Int -- gör om input till int
      if intCh == 27  -- ESC key 
        then do
          render initialState scr -- starta om spelet
          gameLoop initialState scr
        else waitForEsc scr -- annars vänta på ESC
>>>>>>> 26e33e3113aceb15c7eb4e02f166e377f0e46748

main :: IO ()
main = do
  scr <- initScr -- skapar ett Window 
  cBreak True -- ger program tillgång till input direkt, utan att vänta på "enter"
  echo False -- gör så att input inte syns på skärmen
  cursSet CursorInvisible -- tar bort cursor, jobbigt med blinkande cursor!
  keypad scr True -- så vi kan få input från piltangenterna (spelare 1)
  timeout 100 -- timeout för getch, så att vi inte fastnar i input
  render initialState scr
  gameLoop initialState scr
  endWin -- städar upp och stänger fönster
