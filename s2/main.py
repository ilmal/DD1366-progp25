import sys
import string

class Token:
    def __init__(self, type_, value, line):
        self.type = type_  # eg "FORW", "INT", "PERIOD", "ERROR"
        self.value = value
        self.line = line

class Lexer:
    def __init__(self, text):
        self.text = text
        self.pos = 0
        self.line = 1
        self.current_char = self.text[self.pos] if self.text else None

    def error(self):
        return Token("ERROR", None, self.line)

    def advance(self):
        # flytta till nästa tecken
        if self.current_char == "\n":
            self.line += 1
        self.pos += 1
        if self.pos >= len(self.text):
            # om vi är vid slutet av texten
            self.current_char = None
        else:
            # annars flytta till nästa tecken
            self.current_char = self.text[self.pos]

    def skip_whitespace(self):
        # hoppa över mellanslag, tabb och radbrytning
        while self.current_char is not None and self.current_char in " \t\r":
            self.advance()

    def skip_comment(self):
        # kommentar '%' ignorerar resten av raden
        while self.current_char is not None and self.current_char != "\n":
            self.advance()

    def kommando(self):
        # identifiera kommando
        result = ""
        while self.current_char is not None and (self.current_char in string.ascii_letters):
            result += self.current_char
            self.advance()
        # gör stor bokstäver
        result_up = result.upper()
        if result_up in {"FORW", "BACK", "LEFT", "RIGHT", "DOWN", "UP", "COLOR", "REP"}:
            # om det är ett känt kommando
            return Token(result_up, result_up, self.line)
        else:
            # om det inte är ett känt kommando
            return Token("ERROR", result, self.line)

    def number(self):
        result = ""
        # heltal
        while self.current_char is not None and self.current_char.isdigit():
            result += self.current_char
            self.advance()
        return Token("INT", result, self.line)

    def hex_color(self):
        # Förväntar sig '#' följt av exakt 6 hex-tecken
        result = "#"
        self.advance()  # konsumerar '#'
        count = 0
        while self.current_char is not None and count < 6 and (self.current_char in string.digits or self.current_char.upper() in "ABCDEF"):
            result += self.current_char
            self.advance()
            count += 1
        if count == 6:
            return Token("HEX", result, self.line)
        else:
            return Token("ERROR", result, self.line)

    def get_next_token(self):
        # Hämta nästa token, None om slutet av texten
        while self.current_char is not None:
            if self.current_char == "\n":
                self.advance()
                continue

            if self.current_char == "%":
                self.skip_comment()
                continue
            
            # om mellanslag, tabb eller radbrytning
            if self.current_char in " \t\r":
                self.skip_whitespace()
                continue
            
            # om det är en bokstav
            if self.current_char.isalpha():
                return self.kommando()

            # om det är ett heltal
            if self.current_char.isdigit():
                return self.number()

            # om det är en punkt
            if self.current_char == ".":
                self.advance()
                return Token("PERIOD", ".", self.line)

            # om det är ett citattecken
            if self.current_char == "\"":
                self.advance()
                return Token("QUOTE", "\"", self.line)

            # om det är ett hashtagg
            if self.current_char == "#":
                return self.hex_color()

            # Om inget matchar:
            err = self.error()
            self.advance()
            return err

        return Token("EOF", None, self.line)

    def tokenize(self):
        tokens = []
        while True:
            tok = self.get_next_token()
            tokens.append(tok)
            if tok.type == "EOF" or tok.type == "ERROR":
                break
        return tokens

class Parser:
    def __init__(self, tokens):
        self.tokens = tokens
        self.pos = 0

    def error(self, token):
        print(f"Syntaxfel på rad {token.line}")
        import sys
        sys.exit(0)

    def current_token(self):
        if self.pos < len(self.tokens):
            return self.tokens[self.pos]
        return Token("EOF", None, self.tokens[-1].line if self.tokens else 1)

    def eat(self, token_type):
        token = self.current_token()
        if token.type == token_type:
            self.pos += 1
            return token
        else:
            self.error(token)

    def parse(self):
        commands = []
        while self.current_token().type != "EOF":
            if self.current_token().type == "ERROR":
                self.error(self.current_token())
            cmd = self.command()
            commands.append(cmd)
        return {"type": "Program", "commands": commands}

    def command(self):
        token = self.current_token()
        if token.type == "FORW":
            return self.forw()
        elif token.type == "BACK":
            return self.back()
        elif token.type == "LEFT":
            return self.left()
        elif token.type == "RIGHT":
            return self.right()
        elif token.type == "DOWN":
            return self.down()
        elif token.type == "UP":
            return self.up()
        elif token.type == "COLOR":
            return self.color()
        else:
            self.error(token)

    def forw(self):
        tok = self.eat("FORW")
        num = self.eat("INT")
        self.eat("PERIOD")
        return {"type": "Forw", "distance": int(num.value), "line": tok.line}

    def back(self):
        tok = self.eat("BACK")
        num = self.eat("INT")
        self.eat("PERIOD")
        return {"type": "Back", "distance": int(num.value), "line": tok.line}

    def left(self):
        tok = self.eat("LEFT")
        num = self.eat("INT")
        self.eat("PERIOD")
        return {"type": "Left", "angle": int(num.value), "line": tok.line}

    def right(self):
        tok = self.eat("RIGHT")
        num = self.eat("INT")
        self.eat("PERIOD")
        return {"type": "Right", "angle": int(num.value), "line": tok.line}

    def down(self):
        tok = self.eat("DOWN")
        self.eat("PERIOD")
        return {"type": "Down", "line": tok.line}

    def up(self):
        tok = self.eat("UP")
        self.eat("PERIOD")
        return {"type": "Up", "line": tok.line}

    def color(self):
        tok = self.eat("COLOR")
        hex_tok = self.eat("HEX")
        self.eat("PERIOD")
        return {"type": "Color", "hex_value": hex_tok.value, "line": tok.line}



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
    tokens = lexer.tokenize()
    # Vid lexikala fel: skriv ut syntaxfel
    for token in tokens:
        if token.type == "ERROR":
            print(f"Syntaxfel på rad {token.line}")
            return

    parser = Parser(tokens)
    ast = parser.parse()

    print(ast)

if __name__ == '__main__':
    main()
