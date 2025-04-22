#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import sys
import math
import re
import enum
from io import StringIO

# -----------------------------------------------------------------------------
# --- Token and Lexer Definition ---
# -----------------------------------------------------------------------------

class TokenType(enum.Enum):
    """Defines the different types of tokens."""
    DOWN = "DOWN"
    LEFT = "LEFT"
    RIGHT = "RIGHT"
    NUM = "NUM"         # Integer number
    INVALID = "INVALID" # Unrecognized character/sequence
    FORW = "FORW"
    DOT = "DOT"         # Period '.'
    EOF = "EOF"         # End Of File/Input
    UP = "UP"
    REP = "REP"
    BACK = "BACK"
    COLOR = "COLOR"
    LEGITCOLOR = "LEGITCOLOR" # Hex color like #ABCDEF
    QUOTE = "QUOTE"     # Double quote '"'
    # Internal tokens, not directly from language spec but useful for lexer
    COMMENT = "COMMENT"
    NEWLINE = "NEWLINE"
    WHITESPACE = "WHITESPACE"

class Token:
    """Represents a token identified by the lexer."""
    def __init__(self, type, value=None, line=1):
        self.type = type
        self.value = value # Data associated (e.g., number value, color string)
        self.line = line   # Line number where the token starts

    def __repr__(self):
        return f"Token({self.type.name}, {repr(self.value)}, L{self.line})"

class SyntaxError(Exception):
    """Custom exception for syntax errors found during parsing."""
    def __init__(self, line):
        super().__init__(f"Syntaxfel på rad {line}")
        self.line = line

class Lexer:
    """
    Performs lexical analysis on the input text, breaking it into tokens.
    """
    def __init__(self, text):
        self.text = text.upper() # Leonardo language is case-insensitive
        self.tokens = []
        self.current_line = 1

    def tokenize(self):
        """Processes the input text and returns a list of tokens."""
        # Regex patterns for different token types. Order matters.
        # Comments must be handled first. Keywords need careful boundaries.
        token_specification = [
            (TokenType.COMMENT,    r"%.*"),            # Comment line
            (TokenType.NEWLINE,    r"\n"),             # Newline
            (TokenType.WHITESPACE, r"[ \t]+"),         # Whitespace (ignored later)
            (TokenType.FORW,       r"FORW"),           # Keywords
            (TokenType.BACK,       r"BACK"),
            (TokenType.LEFT,       r"LEFT"),
            (TokenType.RIGHT,      r"RIGHT"),
            (TokenType.DOWN,       r"DOWN"),
            (TokenType.UP,         r"UP"),
            (TokenType.COLOR,      r"COLOR"),
            (TokenType.REP,        r"REP"),
            (TokenType.LEGITCOLOR, r"\#[0-9A-F]{6}"), # Hex color format
            (TokenType.NUM,        r"[1-9][0-9]*"),    # Positive integer
            (TokenType.DOT,        r"\."),             # Dot separator
            (TokenType.QUOTE,      r"\""),             # Quote for REP blocks
            (TokenType.INVALID,    r"."),              # Any other single character is invalid
        ]

        # Combine patterns into a single regex
        tok_regex = '|'.join(f'(?P<{pair[0].name}>{pair[1]})' for pair in token_specification)
        get_token = re.compile(tok_regex).match

        pos = 0
        while pos < len(self.text):
            match = get_token(self.text, pos)
            if match:
                token_type_str = match.lastgroup
                value = match.group(token_type_str)
                token_type = TokenType[token_type_str]

                if token_type == TokenType.COMMENT:
                    # Comments are ignored but might contain a newline
                    if '\n' in value:
                        self.current_line += value.count('\n')
                    # Don't add comment tokens to the list
                elif token_type == TokenType.NEWLINE:
                    self.current_line += 1
                    # Don't add newline tokens to the list
                elif token_type == TokenType.WHITESPACE:
                    pass # Ignore whitespace
                elif token_type == TokenType.INVALID:
                    # Found an invalid character
                    self.tokens.append(Token(TokenType.INVALID, value, self.current_line))
                    # Note: In the Java code, INVALID is only added if skipping characters
                    # or at the end. Here, we explicitly add it if a character doesn't match
                    # any known pattern (because '.' is the last resort).
                    # This token signals an error to the caller.
                    # We can stop lexing after the first error if desired,
                    # or collect all tokens including errors.
                    # Let's signal the error and add it.
                else:
                    # Handle specific value conversions
                    token_value = value
                    if token_type == TokenType.NUM:
                        token_value = int(value)
                    elif token_type == TokenType.LEGITCOLOR:
                        token_value = str(value) # Keep as string

                    self.tokens.append(Token(token_type, token_value, self.current_line))

                pos = match.end()
            else:
                # This case should ideally not happen if the INVALID pattern covers everything
                # But if it does, it means something is wrong. Report and advance.
                self.tokens.append(Token(TokenType.INVALID, self.text[pos], self.current_line))
                pos += 1

        self.tokens.append(Token(TokenType.EOF, line=self.current_line))
        return self.tokens


# -----------------------------------------------------------------------------
# --- Parse Tree (AST) Node Definitions ---
# -----------------------------------------------------------------------------

class ParseTree:
    """Abstract base class for all nodes in the parse tree."""
    def evaluate(self, leonardo):
        raise NotImplementedError

class ExprNode(ParseTree):
    """Represents a sequence of instructions."""
    def __init__(self, instructions):
        self.instructions = instructions # List of ParseTree nodes

    def evaluate(self, leonardo):
        for instruction in self.instructions:
            instruction.evaluate(leonardo)

class InstructionNode(ParseTree):
    """Abstract base class for single instructions."""
    pass

class MoveNode(InstructionNode):
    """Represents FORW or BACK instruction."""
    def __init__(self, type, units):
        self.type = type # TokenType.FORW or TokenType.BACK
        self.units = units

    def evaluate(self, leonardo):
        if self.type == TokenType.FORW:
            leonardo.move_forwards(self.units)
        elif self.type == TokenType.BACK:
            leonardo.move_backwards(self.units)

class TurnNode(InstructionNode):
    """Represents LEFT or RIGHT instruction."""
    def __init__(self, type, degrees):
        self.type = type # TokenType.LEFT or TokenType.RIGHT
        self.degrees = degrees

    def evaluate(self, leonardo):
        if self.type == TokenType.LEFT:
            leonardo.turn_left(self.degrees)
        elif self.type == TokenType.RIGHT:
            leonardo.turn_right(self.degrees)

class PenNode(InstructionNode):
    """Represents UP or DOWN instruction."""
    def __init__(self, type):
        self.type = type # TokenType.UP or TokenType.DOWN

    def evaluate(self, leonardo):
        if self.type == TokenType.UP:
            leonardo.move_pen_up()
        elif self.type == TokenType.DOWN:
            leonardo.move_pen_down()

class ColorNode(InstructionNode):
    """Represents COLOR instruction."""
    def __init__(self, color_hex):
        self.color_hex = color_hex # Hex string like #RRGGBB

    def evaluate(self, leonardo):
        leonardo.change_color(self.color_hex)

class RepeatNode(InstructionNode):
    """Represents REP instruction."""
    def __init__(self, repeats, expression):
        self.repeats = repeats
        self.expression = expression # Can be a single InstructionNode or an ExprNode

    def evaluate(self, leonardo):
        for _ in range(self.repeats):
            self.expression.evaluate(leonardo)


# -----------------------------------------------------------------------------
# --- Parser Definition ---
# -----------------------------------------------------------------------------

class Parser:
    """
    Parses a sequence of tokens into a Parse Tree (AST) according to the
    Leonardo grammar using recursive descent.
    """
    def __init__(self, tokens):
        self.tokens = tokens
        self.current_token_index = 0

    def _current_token(self):
        """Returns the current token without consuming it."""
        if self.current_token_index < len(self.tokens):
            return self.tokens[self.current_token_index]
        return self.tokens[-1] # Should be EOF

    def _peek_token(self):
        """Alias for _current_token for clarity."""
        return self._current_token()

    def _next_token(self):
        """Consumes and returns the current token, advances the index."""
        token = self._current_token()
        if token.type != TokenType.EOF:
            self.current_token_index += 1
        return token

    def _expect_token(self, expected_type):
        """Consumes the next token, raises SyntaxError if it's not the expected type."""
        token = self._next_token()
        if token.type != expected_type:
            # Use the line number of the unexpected token for the error
            raise SyntaxError(token.line)
        return token

    def parse(self):
        """Starts the parsing process. Expects a sequence of instructions (Expr)."""
        ast = self._parse_expr()
        # After parsing the main expression, we should be at the end of input
        if self._current_token().type != TokenType.EOF:
            # If not EOF, it means there were leftover tokens that didn't fit the grammar
            raise SyntaxError(self._current_token().line)
        return ast

    def _parse_expr(self):
        """Parses a sequence of instructions."""
        instructions = []
        # Keep parsing instructions as long as the next token is a valid instruction start
        while self._peek_token().type in {TokenType.FORW, TokenType.BACK, TokenType.LEFT,
                                          TokenType.RIGHT, TokenType.DOWN, TokenType.UP,
                                          TokenType.COLOR, TokenType.REP}:
            instructions.append(self._parse_instruction())

        # If only one instruction was found, it might not need an ExprNode wrapper,
        # but using ExprNode consistently simplifies things, especially for REP.
        return ExprNode(instructions)


    def _parse_instruction(self):
        """Parses a single instruction."""
        token = self._peek_token()
        line_num = token.line # Keep track of line number for errors

        if token.type == TokenType.FORW or token.type == TokenType.BACK:
            self._next_token() # Consume FORW/BACK
            num_token = self._expect_token(TokenType.NUM)
            self._expect_token(TokenType.DOT)
            return MoveNode(token.type, num_token.value)

        elif token.type == TokenType.LEFT or token.type == TokenType.RIGHT:
            self._next_token() # Consume LEFT/RIGHT
            num_token = self._expect_token(TokenType.NUM)
            self._expect_token(TokenType.DOT)
            return TurnNode(token.type, num_token.value)

        elif token.type == TokenType.UP or token.type == TokenType.DOWN:
            self._next_token() # Consume UP/DOWN
            self._expect_token(TokenType.DOT)
            return PenNode(token.type)

        elif token.type == TokenType.COLOR:
            self._next_token() # Consume COLOR
            color_token = self._expect_token(TokenType.LEGITCOLOR)
            self._expect_token(TokenType.DOT)
            return ColorNode(color_token.value)

        elif token.type == TokenType.REP:
            self._next_token() # Consume REP
            repeats_token = self._expect_token(TokenType.NUM)
            repeats = repeats_token.value

            # Check if the repetition body is quoted or a single instruction
            if self._peek_token().type == TokenType.QUOTE:
                self._expect_token(TokenType.QUOTE) # Consume opening quote
                # Inside quotes, we expect a sequence of instructions (possibly empty)
                repeated_expr = self._parse_expr()
                self._expect_token(TokenType.QUOTE) # Consume closing quote
                return RepeatNode(repeats, repeated_expr)
            else:
                # If not quoted, expect exactly one instruction after REP N
                single_instruction = self._parse_instruction()
                return RepeatNode(repeats, single_instruction)
        else:
            # This path should not be reached if _parse_expr checks token types correctly
            raise SyntaxError(line_num)


# -----------------------------------------------------------------------------
# --- Leonardo Turtle Graphics Interpreter ---
# -----------------------------------------------------------------------------

class Leonardo:
    """
    Represents the turtle state and executes drawing commands.
    """
    def __init__(self):
        self.x = 0.0
        self.y = 0.0
        self.direction = 0.0 # Angle in degrees (0=right, 90=up, 180=left, 270=down)
        self.color = "#0000FF" # Default blue
        self.is_pen_down = False # Pen starts up
        self.lines = [] # List to store drawn line segments: [(color, (x1, y1), (x2, y2)), ...]
        # self.op_history = [] # Optional: Track operations like in Java

    def _add_history(self, op_string):
        # self.op_history.append(op_string)
        # self.op_history.append(self.__str__()) # Track state after op
        pass # History not strictly required by the problem output

    def move_pen_up(self):
        self._add_history("movePenUp")
        self.is_pen_down = False

    def move_pen_down(self):
        self._add_history("movePenDown")
        self.is_pen_down = True

    def turn_right(self, degrees):
        self._add_history(f"turnRight {degrees}")
        self.direction -= degrees
        self.direction %= 360 # Keep angle within 0-359 degrees

    def turn_left(self, degrees):
        self._add_history(f"turnLeft {degrees}")
        self.direction += degrees
        self.direction %= 360 # Keep angle within 0-359 degrees

    def _move(self, units):
        """Internal helper to calculate new position."""
        # Convert direction from degrees to radians for math functions
        rad = math.radians(self.direction)
        new_x = self.x + units * math.cos(rad)
        new_y = self.y + units * math.sin(rad)
        return new_x, new_y

    def move_forwards(self, units):
        self._add_history(f"moveForwards {units}")
        prev_x, prev_y = self.x, self.y
        self.x, self.y = self._move(units)
        if self.is_pen_down:
            # Store the line segment with current color and coordinates
            self.lines.append((self.color, (prev_x, prev_y), (self.x, self.y)))

    def move_backwards(self, units):
        self._add_history(f"moveBackwards {units}")
        prev_x, prev_y = self.x, self.y
        # Moving backwards is like moving forwards with negative units
        self.x, self.y = self._move(-units)
        if self.is_pen_down:
            self.lines.append((self.color, (prev_x, prev_y), (self.x, self.y)))

    def change_color(self, color_hex):
        # Basic validation (could be more robust)
        if re.match(r"^#[0-9A-F]{6}$", color_hex):
            self._add_history(f"changeColor {color_hex}")
            self.color = color_hex
        else:
            # Handle invalid color format? The lexer should catch this,
            # but maybe add a warning or error here too.
            # For now, assume LEGITCOLOR token guarantees format.
            pass

    def __str__(self):
        """String representation of the turtle's state (for debugging)."""
        return f"{self.color} {self.x:.4f} {self.y:.4f} {self.direction:.1f} PenDown:{self.is_pen_down}"


# -----------------------------------------------------------------------------
# --- Main Execution Logic ---
# -----------------------------------------------------------------------------

def process_text(text, source_name="stdin"):
    """Lexes, parses, and executes the Leonardo code from the text."""
    lexer = Lexer(text)
    try:
        tokens = lexer.tokenize()
        # Check for lexical errors first
        for token in tokens:
            if token.type == TokenType.INVALID:
                print(f"Syntaxfel på rad {token.line}") # Using Swedish error message
                return # Stop processing this source on first error

    except Exception as e: # Catch potential errors during tokenization itself
        print(f"Lexer error in {source_name}: {e}")
        return

    parser = Parser(tokens)
    try:
        ast = parser.parse()
    except SyntaxError as e:
        # Parser already created the error message in Swedish
        print(e)
        return  # Stop processing this source
    except Exception as e:
        # Catch unexpected errors during parsing
        # Determine line number if possible, might be tricky
        print(f"Parser error in {source_name}: {e}")
        return

    # If lexing and parsing succeeded, execute the AST
    leonardo = Leonardo()
    try:
        ast.evaluate(leonardo)
    except Exception as e:
        # Catch potential runtime errors during evaluation
        print(f"Runtime error during execution from {source_name}: {e}")
        return

    # Print the resulting line segments
    for color, (x1, y1), (x2, y2) in leonardo.lines:
        print(f"{color} {x1:.4f} {y1:.4f} {x2:.4f} {y2:.4f}")


def main():
    """Handles command line arguments or reads from stdin."""
    if len(sys.argv) > 1:
        # Process files provided as arguments
        for filename in sys.argv[1:]:
            try:
                with open(filename, "r", encoding="utf-8") as f:
                    text = f.read()
            except Exception as e:
                print(f"Fel vid öppning av fil {filename}: {e}")
                continue # Skip to the next file

            print(f"# Resultat från {filename}:") # Add comment for clarity
            process_text(text, source_name=filename)
            print() # Add a blank line between results from different files
    else:
        # Read from standard input
        # print("# Reading from stdin...") # Optional feedback
        text = sys.stdin.read()
        process_text(text)

if __name__ == '__main__':
    main()