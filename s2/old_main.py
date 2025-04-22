import sys
import string
import math

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
        self.commands = []

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
        result = ""
        while self.current_char is not None and self.current_char.isalpha():
            result += self.current_char
            self.advance()
        result_up = result.upper()
        if result_up in {"FORW", "BACK", "LEFT", "RIGHT", "DOWN", "UP", "COLOR", "REP"}:
            # Kontrollera whitespace för kommandon med parametrar
            if result_up in {"FORW", "BACK", "LEFT", "RIGHT", "COLOR", "REP"}:
                if self.current_char is None or not self.current_char.isspace():
                    return Token("ERROR", result, self.line)
            self.commands.append({
                "command": result_up,
                "line": self.line,
                "dot": False
            })
            return Token(result_up, result_up, self.line)
        else:
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
        while self.current_char is not None:
            if self.current_char == "\n":
                self.advance()
                continue
            if self.current_char == "%":
                self.skip_comment()
                continue
            if self.current_char in " \t\r":
                self.skip_whitespace()
                continue
            if self.current_char.isalpha():
                return self.kommando()
            if self.current_char.isdigit():
                return self.number()
            if self.current_char == ".":
                self.advance()
                if self.commands and not self.commands[-1]["dot"]:
                    self.commands[-1]["dot"] = True
                return Token("PERIOD", ".", self.line)
            if self.current_char == "\"":
                self.advance()
                return Token("QUOTE", "\"", self.line)
            if self.current_char == "#":
                # Kontrollera om senaste kommandot var COLOR
                if len(self.commands) > 0 and self.commands[-1] == "COLOR":
                    return self.hex_color()
                else:
                    # Om inte, rapportera fel
                    err = Token("ERROR", "#", self.line)
                    self.advance()
                    return err
            # Om inget matchar, rapportera fel
            err = Token("ERROR", self.current_char, self.line)
            self.advance()
            return err
        # check that all commands have a period
        for cmd in self.commands:
            if not cmd["dot"] and cmd["command"] not in {"REP"}:
                print("Missing period in command", cmd["command"])
                err = Token("ERROR", "Missing period", cmd["line"])
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
        raise SyntaxError(f"Syntax error on line {token.line}")        

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
        elif token.type == "REP":
            return self.rep_command()
        elif token.type == "HEX":
            self.error(token)  # Hex color not allowed outside COLOR command
        else:
            return self.error(token)
            
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

    def rep_command(self):
        tok = self.eat("REP")
        num = self.eat("INT")  # Antal repetitioner
        next_token = self.current_token()
        if next_token.type == "QUOTE":
            # Sekvens av kommandon inom citattecken
            self.eat("QUOTE")
            commands = []
            while self.current_token().type != "QUOTE":
                if self.current_token().type == "EOF":
                    self.error(self.current_token())  # Saknar avslutande citattecken
                cmd = self.command()
                commands.append(cmd)
            self.eat("QUOTE")
        else:
            # Enskilt kommando utan citattecken
            cmd = self.command()
            commands = [cmd]
        return {"type": "Rep", "reps": int(num.value), "commands": commands, "line": tok.line}


import math

class Executor:
    def __init__(self):
        # Startvärden enligt instruktionerna
        self.x = 0.0
        self.y = 0.0
        self.angle = 0.0  # 0 grader är rakt höger
        self.pen_down = False
        self.color = "#0000FF"  # Blå som startfärg
        self.segments = []  # Lista med linjesegment

    def execute(self, ast):
        if ast["type"] == "Program":
            self.execute_commands(ast["commands"])
        return self.segments

    def execute_commands(self, commands):
        for cmd in commands:
            self.execute_command(cmd)

    def execute_command(self, cmd):
        cmd_type = cmd["type"]
        if cmd_type == "Forw":
            self.move(cmd["distance"], forward=True)
        elif cmd_type == "Back":
            self.move(cmd["distance"], forward=False)
        elif cmd_type == "Left":
            self.angle = (self.angle + cmd["angle"]) % 360
        elif cmd_type == "Right":
            self.angle = (self.angle - cmd["angle"]) % 360
        elif cmd_type == "Down":
            self.pen_down = True
        elif cmd_type == "Up":
            self.pen_down = False
        elif cmd_type == "Color":
            self.color = cmd["hex_value"]
        elif cmd_type == "Rep":
            for _ in range(cmd["reps"]):
                self.execute_commands(cmd["commands"])

    def move(self, distance, forward=True):
        # Beräkna ny position med trigonometri
        direction = 1 if forward else -1
        rad = math.radians(self.angle)
        new_x = self.x + direction * distance * math.cos(rad)
        new_y = self.y + direction * distance * math.sin(rad)
        # Lägg till linjesegment om pennan är nere
        if self.pen_down:
            segment = (self.color, (self.x, self.y), (new_x, new_y))
            self.segments.append(segment)
        # Uppdatera position
        self.x = new_x
        self.y = new_y


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
    for token in tokens:
        if token.type == "ERROR":
            print(f"Syntaxfel på rad {token.line}")
            return

    parser = Parser(tokens)
    try:
        ast = parser.parse()
    except SyntaxError:
        return  # Stop processing this file, continue with the next

    executor = Executor()
    segments = executor.execute(ast)
    for color, (x1, y1), (x2, y2) in segments:
        print(f"{color} {x1:.4f} {y1:.4f} {x2:.4f} {y2:.4f}")


if __name__ == '__main__':
    main()
