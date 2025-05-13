import sys
import re
import math
from enum import Enum

sys.setrecursionlimit(10000)

# Alla olika token-typer 🐢
class TokenType(Enum):
    DOWN = 'DOWN'
    LEFT = 'LEFT'
    RIGHT = 'RIGHT'
    NUM = 'NUM'
    INVALID = 'INVALID'
    FORW = 'FORW'
    DOT = 'DOT'
    EOF = 'EOF'
    UP = 'UP'
    REP = 'REP'
    BACK = 'BACK'
    COLOR = 'COLOR'
    HEX = 'HEX'
    QUOTE = 'QUOTE'

# token class to hold type, data, and row number
class Token:
    def __init__(self, type, data=None, row=0):
        self.type = type
        self.data = data # oftast none, men kan vara färg
        self.row = row

# Lexern - kollar att syntax är korrekt
class Lexer:
    def __init__(self, text):
        # Sätt all text till stora bokstäver -> för case insensitivity
        self.text = text.upper()
        self.tokens = []
        # Starta med att läsa in första token
        self.current_token = 0
        self.tokenize()

    # ska hitta alla tokens
    def tokenize(self):
        # Mega regex pattern to match all tokens, if not matched, it will be invalid
        pattern = r"""(?x)
            (%.*\n)                         # comment with newline
            | (\.)                          # DOT
            | (FORW)(\n|\040|\t|%.*\n)      # FORW followed by newline, space, tab, or comment
            | (BACK)(\n|\040|\t|%.*\n)      # BACK
            | (LEFT)(\n|\040|\t|%.*\n)      # LEFT
            | (RIGHT)(\n|\040|\t|%.*\n)     # RIGHT
            | (DOWN)                        # DOWN
            | (UP)                          # UP
            | (COLOR)(\n|\040|\t|%.*\n)     # COLOR
            | (\#[0-9A-F]{6})               # HEX
            | (REP)(\n|\040|\t|%.*\n)       # REP
            | (")                           # QUOTE
            | ([1-9][0-9]*)(\n|\040|\t|\.|%.*\n)  # number followed by newline, space, tab, dot, or comment
            | (\n)                          # newline
            | (\040|\t)+                    # whitespace
            | (%.*)                         # comment without newline (at end of file)
        """

        # Find all matches in the text
        # and create tokens based on the matched groups
        current_row = 1
        input_pos = 0
        for m in re.finditer(pattern, self.text):
            # Handle invalid characters between matches
            # input_pos -> vilken position vi borde ha UTAN invalids
            # m.start() -> vilken position nästa match startar på
            # ifall de inte stämmer överens måste det finnas invalid nånstans
            if m.start() > input_pos:
                self.tokens.append(Token(TokenType.INVALID, row=current_row))
                input_pos = m.start()
            # Process matched groups
            if m.group(1):  # comment = skip 
                current_row += 1
            elif m.group(2):  # dot
                self.tokens.append(Token(TokenType.DOT, row=current_row))
            elif m.group(3):  # FORW
                self.tokens.append(Token(TokenType.FORW, row=current_row))
                # kollar om newline/kommentar så nästa data är på nästa rad minst
                if m.group(4) == '\n' or m.group(4).startswith('%'): 
                    current_row += 1 
            elif m.group(5):  # BACK
                self.tokens.append(Token(TokenType.BACK, row=current_row))
                if m.group(6) == '\n' or m.group(6).startswith('%'):
                    current_row += 1
            elif m.group(7):  # LEFT
                self.tokens.append(Token(TokenType.LEFT, row=current_row))
                if m.group(8) == '\n' or m.group(8).startswith('%'):
                    current_row += 1
            elif m.group(9):  # RIGHT
                self.tokens.append(Token(TokenType.RIGHT, row=current_row))
                if m.group(10) == '\n' or m.group(10).startswith('%'):
                    current_row += 1
            elif m.group(11):  # DOWN
                self.tokens.append(Token(TokenType.DOWN, row=current_row))
            elif m.group(12):  # UP
                self.tokens.append(Token(TokenType.UP, row=current_row))
            elif m.group(13):  # COLOR
                self.tokens.append(Token(TokenType.COLOR, row=current_row))
                if m.group(14) == '\n' or m.group(14).startswith('%'):
                    current_row += 1
            elif m.group(15):  # hex color -> add data hexcolor m.group(15) when appending
                self.tokens.append(Token(TokenType.HEX, m.group(15), row=current_row))
            elif m.group(16):  # REP
                self.tokens.append(Token(TokenType.REP, row=current_row))
                if m.group(17) == '\n' or m.group(17).startswith('%'):
                    current_row += 1
            elif m.group(18):  # quote
                self.tokens.append(Token(TokenType.QUOTE, row=current_row))
            elif m.group(19):  # number
                num = int(m.group(19)) # gör om till int
                self.tokens.append(Token(TokenType.NUM, num, row=current_row))
                following = m.group(20)
                if following == '.':
                    self.tokens.append(Token(TokenType.DOT, row=current_row))
                elif following == '\n' or following.startswith('%'):
                    current_row += 1
            elif m.group(21):  # newline
                current_row += 1
            elif m.group(22):  # whitespace
                pass  # ignore
            input_pos = m.end() # always set input_pos to end of current match
        # checker om det finns invalid på slutet av programmet
        if input_pos < len(self.text):
            self.tokens.append(Token(TokenType.INVALID, row=current_row))
        self.tokens.append(Token(TokenType.EOF, row=current_row)) # add end of file

    # look at current token without moving forward in token list
    def peek_token(self):
        # Return the current token without consuming it
        # OM det finns tokens kvar i listan -> returnera värdet av nuvarande token
        # OTHERWISE, returnera eof eller 1 om ingen lista finns 
        return self.tokens[self.current_token] if self.current_token < len(self.tokens) else Token(TokenType.EOF, row=self.tokens[-1].row if self.tokens else 1)

    # kollar på nuvarande token och inkrementera pekaren
    def next_token(self):
        # Return the next token and consume it, nom nom nom
        token = self.peek_token()
        self.current_token += 1
        return token # returnera den konsumerade tokenen nom nom 

# parse tree - basklass
class ParseTree:
    def evaluate(self, Leona):
        pass

    def pretty_print(self, indent=0):
        return "ParseTree"

# importerar ParseTree
# klass som innehåller alla instruktioner/noder i trädet
# en gren i trädet
class ExprNode(ParseTree):
    def __init__(self, instructions):
        self.instructions = instructions

    def evaluate(self, Leona):
        for inst in self.instructions:
            inst.evaluate(Leona)

    def pretty_print(self, indent=0, prefix=""):
        result = prefix + "ExprNode\n"
        for i, inst in enumerate(self.instructions):
            is_last = i == len(self.instructions) - 1
            new_prefix = prefix + ("└── " if is_last else "├── ")
            result += inst.pretty_print(indent + 1, new_prefix)
            if not is_last:
                result += prefix + "│   " + "\n"
        return result

# Löv
class MoveNode(ParseTree):
    def __init__(self, type, units):
        self.type = type
        self.units = units

    def evaluate(self, Leona):
        if self.type == TokenType.FORW:
            Leona.move_forwards(self.units)
        elif self.type == TokenType.BACK:
            Leona.move_backwards(self.units)

    def pretty_print(self, indent=0, prefix=""):
        return prefix + f"MoveNode({self.type.value}, units={self.units})\n"

class TurnNode(ParseTree):
    def __init__(self, type, degrees):
        self.type = type
        self.degrees = degrees

    def evaluate(self, Leona):
        if self.type == TokenType.LEFT:
            Leona.turn_left(self.degrees)
        elif self.type == TokenType.RIGHT:
            Leona.turn_right(self.degrees)

    def pretty_print(self, indent=0, prefix=""):
        return prefix + f"TurnNode({self.type.value}, degrees={self.degrees})\n"

class PenNode(ParseTree):
    def __init__(self, type):
        self.type = type

    def evaluate(self, Leona):
        if self.type == TokenType.UP:
            Leona.move_pen_up()
        elif self.type == TokenType.DOWN:
            Leona.move_pen_down()

    def pretty_print(self, indent=0, prefix=""):
        return prefix + f"PenNode({self.type.value})\n"

class ColorNode(ParseTree):
    def __init__(self, color):
        self.color = color

    def evaluate(self, Leona):
        Leona.change_color(self.color)

    def pretty_print(self, indent=0, prefix=""):
        return prefix + f"ColorNode({self.color})\n"

class RepeatNode(ParseTree):
    def __init__(self, repeats, subtree):
        self.repeats = repeats
        self.subtree = subtree

    def evaluate(self, Leona):
        for _ in range(self.repeats):
            self.subtree.evaluate(Leona)

    def pretty_print(self, indent=0, prefix=""):
        result = prefix + f"RepeatNode(repeats={self.repeats})\n"
        new_prefix = prefix + "│   "
        result += self.subtree.pretty_print(indent + 1, new_prefix)
        return result

# parser - kollar att grammatiken är korrekt
class Parser:
    def __init__(self, lexer):
        self.lexer = lexer
        self.last_row = 1  # Startvärde

    def consume_token(self):
        # nom nom token
        token = self.lexer.next_token()
        self.last_row = token.row
        return token

    # expect_token checkar om nästa token är av rätt typ
    def expect_token(self, expected_type):
        # tjuvkolla lite utan å käka upp token
        t = self.lexer.peek_token()
        # om den är av rätt typ så käka upp den, nom nom
        if t.type == expected_type:
            return self.lexer.next_token()
        # om den inte är av rätt typ så har något gått snett
        elif t.type == TokenType.INVALID:
            # Fallback to last valid row if needed
            raise SyntaxError(f"Syntaxfel på rad {t.row}")
        else:
            error_row = self.last_row if t.type == TokenType.EOF else t.row
            raise SyntaxError(f"Syntaxfel på rad {error_row}")


    def parse(self):
        # skapa träd från lexer 
        tree = self.expr()
        t = self.lexer.next_token()
        if t.type != TokenType.EOF:
            raise SyntaxError(f"Syntaxfel på rad {t.row}")
        return tree

    def expr(self):
        instructions = []
        valid_types = {TokenType.FORW, TokenType.BACK, TokenType.LEFT, TokenType.RIGHT,
                       TokenType.DOWN, TokenType.UP, TokenType.COLOR, TokenType.REP}
        # loopar tills vi når EOF eller en ogiltig token
        while self.lexer.peek_token().type in valid_types:
            instructions.append(self.instruction())
        return ExprNode(instructions)

    def instruction(self):
        # hoppa till nästa token, nom nom
        t = self.consume_token()
        command_row = t.row

        # mycket basic saker, typ vill ha siffra efter FORW eller BACK sedan punkt mm. Basic stuff u know
        if t.type in {TokenType.FORW, TokenType.BACK}:
            num_token = self.expect_token(TokenType.NUM)
            try:
                self.expect_token(TokenType.DOT)
            except SyntaxError as e:
                raise SyntaxError(e)
            return MoveNode(t.type, num_token.data)
        elif t.type in {TokenType.LEFT, TokenType.RIGHT}:
            num_token = self.expect_token(TokenType.NUM)
            try:
                self.expect_token(TokenType.DOT)
            except SyntaxError as e:
                raise SyntaxError(e)
            return TurnNode(t.type, num_token.data)
        elif t.type in {TokenType.DOWN, TokenType.UP}:
            try:
                self.expect_token(TokenType.DOT)
            except SyntaxError as e:
                raise SyntaxError(e)
            return PenNode(t.type)
        elif t.type == TokenType.COLOR: 
            color_token = self.expect_token(TokenType.HEX)
            try:
                self.expect_token(TokenType.DOT)
            except SyntaxError as e:
                raise SyntaxError(e)
            return ColorNode(color_token.data)
        elif t.type == TokenType.REP:
            repeats_token = self.expect_token(TokenType.NUM)
            
            if self.check_quote(repeats_token.row):
                self.consume_token()            # consume the opening QUOTE
                
                # checka att quote följs direkt av commando
                if self.lexer.peek_token().type not in {TokenType.FORW, TokenType.BACK,
                                                    TokenType.LEFT, TokenType.RIGHT, 
                                                    TokenType.DOWN, TokenType.UP, TokenType.COLOR, 
                                                    TokenType.REP}:
                    raise SyntaxError(f"Syntaxfel på rad {self.lexer.peek_token().row}")
                subtree = self.expr()          # parse everything up to next non-instruction
                
                # check last row if quotation is closed 
                # if not self.check_quote(self.last_row):
                #     # if it wasn’t a QUOTE, throw with the right row
                #     raise SyntaxError(f"Syntaxfel på rad {self.last_row} rep2")
                
                next_token = self.lexer.peek_token()
                if next_token.type == TokenType.INVALID:
                    raise SyntaxError(f"Syntaxfel på rad {next_token.row}")
                
                if next_token.type != TokenType.QUOTE:
                    raise SyntaxError(f"Syntaxfel på rad {self.last_row}")

                self.consume_token()            # consume the closing QUOTE
                return RepeatNode(repeats_token.data, subtree)
            else:
                # no quotes → single instruction
                subtree = self.instruction()
                return RepeatNode(repeats_token.data, subtree)
            
        ## new method
    def check_quote(self, last_row):
        t = self.lexer.peek_token()
        if t.type == TokenType.QUOTE:
            return True
        elif t.type == TokenType.EOF:
            # exactly like Java’s chkQuote: throw on EOF
            raise SyntaxError(f"Syntaxfel på rad {last_row}")
        return False


# Leona class for turtle 🐢 - Exekverar vårt parsetree
class Leona:
    def __init__(self):
        self.x = 0.0
        self.y = 0.0
        self.direction = 0.0  # degrees
        self.color = "#0000FF"
        self.is_pen_down = False
        self.lines = []

    def move_pen_up(self):
        self.is_pen_down = False

    def move_pen_down(self):
        self.is_pen_down = True

    def turn_right(self, degrees):
        self.direction -= degrees

    def turn_left(self, degrees):
        self.direction += degrees

    def move_forwards(self, units):
        prev_x = self.x
        prev_y = self.y
        self.move(units)
        if self.is_pen_down:
            # Format coordinates to match Python four decimal places 🐢🐢🐢🐢 
            self.lines.append(f"{self.color} {prev_x:.4f} {prev_y:.4f} {self.x:.4f} {self.y:.4f}")

    def move_backwards(self, units):
        prev_x = self.x
        prev_y = self.y
        self.move(-units)
        if self.is_pen_down:
            self.lines.append(f"{self.color} {prev_x:.4f} {prev_y:.4f} {self.x:.4f} {self.y:.4f}")

    def move(self, units):
        # Simplified angle calculation: directly convert degrees to radians
        rad = math.radians(self.direction)
        self.x += units * math.cos(rad)
        self.y += units * math.sin(rad)

    def change_color(self, color):
        self.color = color

# Main function with file input handling from your Python snippet
def main():
    # nils gjorde en skum lösning
    if len(sys.argv) > 1:
        for filename in sys.argv[1:]:
            try:
                with open(filename, "r", encoding="utf-8") as f:
                    text = f.read()
            except Exception as e:
                print(f"Fel vid öppning av fil {filename}: {e}")
                continue
            print(f"Resultat från {filename}:")
            process_text(text)
            print()
    else:
        text = sys.stdin.read() # läser in texten från filen normalt
        process_text(text)

def process_text(text):
    lexer = Lexer(text) # fixar lista med tokens med lexer
    parser = Parser(lexer) # kollar grammatiken
    try:
        parsed_tree = parser.parse() # sätter igång parsern
        print("Parse Tree:") # printa träd 
        print(parsed_tree.pretty_print()) 
    except SyntaxError as e:
        print(e)
        return #                                                                               🐢 🐢
    leona = Leona() 
    parsed_tree.evaluate(leona) #                                                              🐢    🐢
    for line in leona.lines: #                                                                   🐢🐢🐢
        print(line)

if __name__ == '__main__':
    main()
