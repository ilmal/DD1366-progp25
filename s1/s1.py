# coding: utf-8
########################################################################
# Mall för labb S1, DD1361 Programmeringsparadigm.
# Författare: Per Austrin
########################################################################
########################################################################
# Dessa funktioner är det som ska skrivas för att lösa de olika
# uppgifterna i labblydelsen.
########################################################################

def dna():          # uppgift 1
    # ^  -> början av strängen
    # $  -> slutet av strängen, alltså att hela strängen matchar
    # [ACGT] -> bara en av A, C, G, T får vara med
    # + -> det måste förekomma minst en gång, alltså inte tillåta en tom sträng
    return r'^[ACGT]+$'


def sorted():       # uppgift 2
    # ^  -> början av strängen
    # 9* -> 0 eller fler 9:or, vi tillåter alltså inga 9 oxå
    # samma sak för alla siffror (däremot får då inte ex en 6a förekomma innan en 7a)
    # $  -> slutet av strängen, alltså att hela strängen matchar
    return r'^9*8*7*6*5*4*3*2*1*0*$'


def hidden1(x):     # uppgift 3
    # indata x är strängen som vi vill konstruera ett regex för att söka efter
    # ^  -> början av strängen
    # .* -> 0 eller fler av vilken karaktär som helst . = vilken karaktär som helst, * = 0 eller fler
    # x  -> den sträng vi vill söka efter
    # .* -> 0 eller fler av vilken karaktär som helst ännu en gång
    # $  -> slutet av strängen, alltså att hela strängen matchar
    return '^.*' + x + '.*$'


def hidden2(x):     # uppgift 4
    # indata x är strängen som vi vill konstruera ett regex för att söka efter
    # Samma som ovan, fast med join. 
    # Ifall vi tar ex progp så gör join att det blir:
    # ^.*p.*r.*o.*g.*p.*$, alltså sätter man ihop varje karaktär med .*
    return '^.*' + '.*'.join(x) + '.*$'

def equation():     # uppgift 5
    return r'^[+\-]?\d+(?:[+\-*/]\d+)*(?:=[+\-]?\d+(?:[+\-*/]\d+)*)?$'


def parentheses():  # uppgift 6
    return r'^(?:\(\)|\(\(\)\)|\(\(\(\)\)\)|\(\(\(\(\)\)\)\)|\(\(\(\(\(\)\)\)\)\))+$'


def sorted3():      # uppgift 7
    asc_triples = []
    for i in range(10):
        for j in range(i+1, 10):
            for k in range(j+1, 10):
                asc_triples.append(f"{i}{j}{k}")
    pattern = "|".join(asc_triples)  # "012|013|014|...|789"
    return rf'^.*(?:{pattern}).*$'


########################################################################
# Raderna nedan är lite testkod som du kan använda för att provköra
# dina regexar.  Koden definierar en main-metod som läser rader från
# standard input och kollar vilka av de olika regexarna som matchar
# indata-raden.  För de två hidden-uppgifterna används söksträngen
# x="test" (kan lätt ändras nedan).  Du behöver inte sätta dig in i hur
# koden nedan fungerar om du inte känner för det.
#
# För att provköra från terminal, kör:
# $ python s1.py
# Skriv in teststrängar:
# [skriv något roligt]
# ...
########################################################################
from sys import stdin
import re
def main():
    def hidden1_test(): return hidden1('test')
    def hidden2_test(): return hidden2('test')
    tasks = [dna, sorted, hidden1_test, hidden2_test, equation, parentheses, sorted3]
    print('Skriv in teststrängar:')
    while True:
        line = stdin.readline().rstrip('\r\n')
        if line == '': break
        for task in tasks:
            result = '' if re.search(task(), line) else 'INTE '
            print('%s(): "%s" matchar %suttrycket "%s"' % (task.__name__, line, result, task()))
if __name__ == '__main__': main()

