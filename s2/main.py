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

# Node class for the parse tree
class Node:
    def __init__(self, type, value=None, children=None):
        self.type = type
        self.value = value
        self.children = children if children is not None else []

    def pretty_print(self, level=0):
        indent = "  " * level
        if self.value:
            print(f"{indent}{self.type}: {self.value}")
        else:
            print(f"{indent}{self.type}")
        for child in self.children:
            child.pretty_print(level + 1)

    def evaluate(self, leona):
        if self.type == "Instruction":
            parts = self.value.split()
            command = parts[0]
            if command == "FORW":
                leona.move_forwards(int(parts[1]))
            elif command == "BACK":
                leona.move_backwards(int(parts[1]))
            elif command == "LEFT":
                leona.turn_left(int(parts[1]))
            elif command == "RIGHT":
                leona.turn_right(int(parts[1]))
            elif command == "DOWN":
                leona.move_pen_down()
            elif command == "UP":
                leona.move_pen_up()
            elif command == "COLOR":
                leona.change_color(parts[1])
            elif command == "REP":
                repeats = int(parts[1])
                for _ in range(repeats):
                    self.children[0].evaluate(leona)
        elif self.type == "Rep":
            self.children[0].evaluate(leona)
        elif self.type == "Expr":
            for child in self.children:
                child.evaluate(leona)

# parser - kollar att grammatiken är korrekt
class Parser:
    def __init__(self, lexer):
        self.lexer = lexer
        self.last_row = 1  # Startvärde
        self.current_token = self.lexer.peek_token()

    def consume(self, expected_type):
        # nom nom token
        token = self.lexer.next_token()
        self.last_row = token.row
        if token.type != expected_type:
            raise SyntaxError(f"Syntaxfel på rad {token.row}: Expected {expected_type}, got {token.type}")
        self.current_token = self.lexer.peek_token()
        return token

    def parse(self):
        # skapa träd från lexer 
        tree = self.expr()
        t = self.lexer.next_token()
        if t.type != TokenType.EOF:
            raise SyntaxError(f"Syntaxfel på rad {t.row}")
        return tree

    def expr(self):
        # <expr> ::= <instruction> <expr> | ε
        if self.current_token.type in {TokenType.FORW, TokenType.BACK, TokenType.LEFT, 
                                       TokenType.RIGHT, TokenType.DOWN, TokenType.UP, 
                                       TokenType.COLOR, TokenType.REP}:
            instr_node = self.instruction()
            expr_node = self.expr()
            return Node("Expr", children=[instr_node, expr_node])
        return Node("Expr")

    def instruction(self):
        # <instruction> ::= FORW NUM DOT | BACK NUM DOT | LEFT NUM DOT | RIGHT NUM DOT
        #                 | DOWN DOT | UP DOT | COLOR HEX DOT | REP NUM <rep>
        token = self.current_token
        command_row = token.row

        if token.type in {TokenType.FORW, TokenType.BACK, TokenType.LEFT, TokenType.RIGHT}:
            command = self.consume(token.type).type.value
            num = self.consume(TokenType.NUM).data
            self.consume(TokenType.DOT)
            return Node("Instruction", value=f"{command} {num}")
        elif token.type in {TokenType.DOWN, TokenType.UP}:
            command = self.consume(token.type).type.value
            self.consume(TokenType.DOT)
            return Node("Instruction", value=command)
        elif token.type == TokenType.COLOR:
            self.consume(TokenType.COLOR)
            hex_val = self.consume(TokenType.HEX).data
            self.consume(TokenType.DOT)
            return Node("Instruction", value=f"COLOR {hex_val}")
        elif token.type == TokenType.REP:
            self.consume(TokenType.REP)
            num = self.consume(TokenType.NUM).data
            rep_node = self.rep()
            return Node("Instruction", value=f"REP {num}", children=[rep_node])
        else:
            raise SyntaxError(f"Syntaxfel på rad {command_row}: Invalid instruction")

    def rep(self):
        # <rep> ::= QUOTE <expr> QUOTE | <instruction>
        if self.current_token.type == TokenType.QUOTE:
            self.consume(TokenType.QUOTE)
            expr_node = self.expr()
            self.consume(TokenType.QUOTE)
            return Node("Rep", children=[expr_node])
        return Node("Rep", children=[self.instruction()])

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
        parsed_tree.pretty_print()
        leona = Leona()
        parsed_tree.evaluate(leona) # exekvera trädet
        for line in leona.lines: # print turtle output
            print(line)
    except SyntaxError as e:
        print(e)
        return #                                                                               🐢 🐢
    except Exception as e:
        print(f"Ett fel uppstod: {e}")
        return #                                                                               🐢 🐢

if __name__ == '__main__':
    main()