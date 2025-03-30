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
  | collision newP2 newState = state { gameOver = True, winner = p1 state }  
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
  wclear scr
  drawBorder state scr
  mapM_ (drawWall scr) (walls state)
  drawPlayer scr (p1 state)
  drawPlayer scr (p2 state)
  refresh

-- Rita en ram runt spelplanen, oxå MONADER!!!!! :D
drawBorder :: GameState -> Window -> IO ()
drawBorder state scr = do
  let w = boardWidth state -- hämta spelplanens storlek
      h = boardHeight state
-- mapM_ är en monadisk variant av map, dvs den tar en monadisk funktion, i detta fall en IO funktion, vi använder _ variant för att ignorera returvärdet
  mapM_ (\x -> do
           wMove scr 0 x
           void $ waddch scr (charToCh '#')
           wMove scr (h-1) x
           void $ waddch scr (charToCh '#')
        ) [0 .. w-1]
  mapM_ (\y -> do
           wMove scr y 0
           void $ waddch scr (charToCh '#')
           wMove scr y (w-1)
           void $ waddch scr (charToCh '#')
        ) [0 .. h-1]

drawWall :: Window -> (Int,Int) -> IO ()
drawWall scr (x,y) = do
  wMove scr y x
  void $ waddch scr (charToCh '#')

dirToChar :: Direction -> Char
dirToChar U = '^'
dirToChar D = 'v'
dirToChar L = '<'
dirToChar R = '>'

drawPlayer :: Window -> Player -> IO ()
drawPlayer scr (Player _ (x,y) d) = do
  wMove scr y x
  void $ waddch scr (charToCh (dirToChar d))

-- Spelloopen
gameLoop :: GameState -> Window -> IO ()
gameLoop state scr = do
  if gameOver state
    then do
      mvWAddStr scr (boardHeight state) 0 ("Game Over! " ++ name (winner state) ++ " vann! Tryck på ESC för att starta om.")
      refresh
      waitForEsc scr
    else do
      ch <- getch
      let intCh = fromIntegral ch :: Int
      let state1 = if intCh == (-1) then state else handleInput intCh state
      let state2 = movePlayers state1
      render state2 scr
      threadDelay 100000  -- 100 ms
      gameLoop state2 scr
  where
    waitForEsc scr = do
      ch <- getch
      let intCh = fromIntegral ch :: Int
      if intCh == 27  -- ESC key
        then do
          render initialState scr
          gameLoop initialState scr
        else waitForEsc scr

main :: IO ()
main = do
  scr <- initScr
  cBreak True
  echo False
  cursSet CursorInvisible
  keypad scr True
  timeout 100
  render initialState scr
  gameLoop initialState scr
  endWin
