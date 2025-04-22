import sys
import re
import math
from enum import Enum

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
    LEGITCOLOR = 'LEGITCOLOR'
    QUOTE = 'QUOTE'

# token class to hold type, data, and row number
class Token:
    def __init__(self, type, data=None, row=0):
        self.type = type
        self.data = data
        self.row = row

# Lexern
class Lexer:
    def __init__(self, text):
        self.text = text.upper()
        self.tokens = []
        self.current_token = 0
        self.tokenize()

    def tokenize(self):
        # Mega regex pattern to match all tokens, if not matched, it will be invalid
        pattern = r"""(?x)
            (%.*\n)                         # comment with newline
            | (\.)                          # dot
            | (FORW)(\n|\040|\t|%.*\n)      # FORW followed by newline, space, tab, or comment
            | (BACK)(\n|\040|\t|%.*\n)      # BACK
            | (LEFT)(\n|\040|\t|%.*\n)      # LEFT
            | (RIGHT)(\n|\040|\t|%.*\n)     # RIGHT
            | (DOWN)                        # DOWN
            | (UP)                          # UP
            | (COLOR)(\n|\040|\t|%.*\n)     # COLOR
            | (\#[0-9A-F]{6})               # hex color
            | (REP)(\n|\040|\t|%.*\n)       # REP
            | (")                           # quote
            | ([1-9][0-9]*)(\n|\040|\t|\.|%.*\n)  # number followed by newline, space, tab, dot, or comment
            | (\n)                          # newline
            | (\040|\t)+                    # whitespace
            | (%.*)                         # comment without newline (at end of file)
        """


        current_row = 1
        input_pos = 0
        for m in re.finditer(pattern, self.text):
            # Handle invalid characters between matches
            if m.start() > input_pos:
                self.tokens.append(Token(TokenType.INVALID, row=current_row))
                input_pos = m.start()
            # Process matched groups
            if m.group(1):  # comment
                current_row += 1
            elif m.group(2):  # dot
                self.tokens.append(Token(TokenType.DOT, row=current_row))
            elif m.group(3):  # FORW
                self.tokens.append(Token(TokenType.FORW, row=current_row))
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
            elif m.group(15):  # hex color
                self.tokens.append(Token(TokenType.LEGITCOLOR, m.group(15), row=current_row))
            elif m.group(16):  # REP
                self.tokens.append(Token(TokenType.REP, row=current_row))
                if m.group(17) == '\n' or m.group(17).startswith('%'):
                    current_row += 1
            elif m.group(18):  # quote
                self.tokens.append(Token(TokenType.QUOTE, row=current_row))
            elif m.group(19):  # number
                num = int(m.group(19))
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
            input_pos = m.end()
        if input_pos < len(self.text):
            self.tokens.append(Token(TokenType.INVALID, row=current_row))
        self.tokens.append(Token(TokenType.EOF, row=current_row))

    def peek_token(self):
        # Return the next token without consuming it
        return self.tokens[self.current_token] if self.current_token < len(self.tokens) else Token(TokenType.EOF, row=self.tokens[-1].row if self.tokens else 1)

    def next_token(self):
        token = self.peek_token()
        self.current_token += 1
        return token

class ParseTree:
    def evaluate(self, leonardo):
        pass

class ExprNode(ParseTree):
    def __init__(self, instructions):
        self.instructions = instructions

    def evaluate(self, leonardo):
        for inst in self.instructions:
            inst.evaluate(leonardo)

class MoveNode(ParseTree):
    def __init__(self, type, units):
        self.type = type
        self.units = units

    def evaluate(self, leonardo):
        if self.type == TokenType.FORW:
            leonardo.move_forwards(self.units)
        elif self.type == TokenType.BACK:
            leonardo.move_backwards(self.units)

class TurnNode(ParseTree):
    def __init__(self, type, degrees):
        self.type = type
        self.degrees = degrees

    def evaluate(self, leonardo):
        if self.type == TokenType.LEFT:
            leonardo.turn_left(self.degrees)
        elif self.type == TokenType.RIGHT:
            leonardo.turn_right(self.degrees)

class PenNode(ParseTree):
    def __init__(self, type):
        self.type = type

    def evaluate(self, leonardo):
        if self.type == TokenType.UP:
            leonardo.move_pen_up()
        elif self.type == TokenType.DOWN:
            leonardo.move_pen_down()

class ColorNode(ParseTree):
    def __init__(self, color):
        self.color = color

    def evaluate(self, leonardo):
        leonardo.change_color(self.color)

class RepeatNode(ParseTree):
    def __init__(self, repeats, subtree):
        self.repeats = repeats
        self.subtree = subtree

    def evaluate(self, leonardo):
        for _ in range(self.repeats):
            self.subtree.evaluate(leonardo)

class Parser:
    def __init__(self, lexer):
        self.lexer = lexer
        # [print(token.type) for token in lexer.tokens]
        self.last_row = 1  # Startvärde

    def consume_token(self):
        token = self.lexer.next_token()
        self.last_row = token.row
        return token

    def expect_token(self, expected_type):
        t = self.lexer.peek_token()
        if t.type == expected_type:
            return self.lexer.next_token()
        elif t.type == TokenType.INVALID:
            # Fallback to last valid row if needed
            raise SyntaxError(f"Syntaxfel på rad {self.last_row}")
        else:
            raise SyntaxError(f"Syntaxfel på rad {self.last_row}")



    def parse(self):
        tree = self.expr()
        t = self.lexer.next_token()
        if t.type != TokenType.EOF:
            raise SyntaxError(f"Syntaxfel på rad {t.row}")
        return tree

    def expr(self):
        instructions = []
        valid_types = {TokenType.FORW, TokenType.BACK, TokenType.LEFT, TokenType.RIGHT,
                       TokenType.DOWN, TokenType.UP, TokenType.COLOR, TokenType.REP}
        while self.lexer.peek_token().type in valid_types:
            instructions.append(self.instruction())
        return ExprNode(instructions)

    def instruction(self):
        t = self.consume_token()
        command_row = t.row

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
            color_token = self.expect_token(TokenType.LEGITCOLOR)
            try:
                self.expect_token(TokenType.DOT)
            except SyntaxError as e:
                raise SyntaxError(e)
            return ColorNode(color_token.data)
        elif t.type == TokenType.REP:
            repeats_token = self.expect_token(TokenType.NUM)
            next_t = self.lexer.peek_token()
            if next_t.type == TokenType.QUOTE:
                quote_token = self.consume_token()  # Consume opening QUOTE
                subtree = self.expr()
                try:
                    closing_quote = self.expect_token(TokenType.QUOTE)
                except SyntaxError as e:
                    raise SyntaxError(e)
                return RepeatNode(repeats_token.data, subtree)
            else:
                subtree = self.instruction()
                return RepeatNode(repeats_token.data, subtree)

# Leonardo class for turtle graphics state and operations
class Leonardo:
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
            # Format coordinates to match Python's four decimal places
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
        text = sys.stdin.read()
        process_text(text)

def process_text(text):
    lexer = Lexer(text)
    parser = Parser(lexer)
    try:
        ast = parser.parse()
    except SyntaxError as e:
        print(e)
        return
    leonardo = Leonardo()
    ast.evaluate(leonardo)
    for line in leonardo.lines:
        print(line)

if __name__ == '__main__':
    main()
