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

    # extra: för ett fixed antal tecken mellan varje karaktär: .{n} istället för .* 
    return r'^.*' + r'.*'.join(x) + r'.*$'

def equation():     # uppgift 5
    sign       = r'[+\-]?'
    number     = r'\d+'
    operator   = r'[+\-*/]'
    
    # ett tal med valfri ledande plus eller minus
    basic_expr = sign + number
    
    # operator följt av ett tal, upprepade noll eller flera gånger
    ops_expr   = r'(' + operator + number + r')*'
    
    # = följt av ett nytt uttryck (samma mönster som ovan), som är valfritt
    eq_expr    = r'(=' + basic_expr + ops_expr + r')?'
    
    return r'^' + basic_expr + ops_expr + eq_expr + r'$'


def parentheses(): # uppgift 6
    inner  = r"(\(\))*"                     # matchar "()" 
    level4 = r"(\(" + inner + r"\))*"       # nästa nivå
    level3 = r"(\(" + level4 + r"\))*"      # nästa nivå
    level2 = r"(\(" + level3 + r"\))*"      # nästa nivå
    level1 = r"(\(" + level2 + r"\))"       # yttersta nivån eg, "()" eller "()()()"
    regex  = r"^(" + level1 + r")+$"        # hela uttrycket
    return regex


def sorted3():      # uppgift 7
    arr = [
        r"01[2-9]",
        r"[0-1]2[3-9]",
        r"[0-2]3[4-9]",
        r"[0-3]4[5-9]",
        r"[0-4]5[6-9]",
        r"[0-5]6[7-9]",
        r"[0-6]7[8-9]",
        r"[0-7]89"
    ]

    return r"^[0-9]*(" + "|".join(arr) + r")[0-9]*$"




# Fråga 2
# Vi kan inte direkt kombinera uppg 5 å 6, vi måste konstruera ett simulerat rekursivt fall då regex i grunden
# inte är rekursivt. Vi kan dock använda oss av en rekursiv funktion som bygger upp en regex-sträng som hanterar både
# paranteser och ekvationer.
# ish:
# def expr_depth1():
#     basic_expr = från tidigare
#     operator = från tidigare
#     term1 = r"(" + basic_expr + "|\(" + basic_expr + "\))"
#     return term1 + "(" + operator + term1 + ")*"
#
#
# def expr_depth2():
#     basic_expr = expr_depth1()
#     operator = från tidigare
#     term1 = r"(" + basic_expr + "|\(" + basic_expr + "\))"
#     return term1 + "(" + operator + term1 + ")*"
#
# osv

# Nej, vi kan inte generalisera lösningen för att hantera n antal siffror med ren regex. Vi kan antingen göra det för hand, 
# eller skriva en funktion som genererar regex för varje siffra. Däremot använder detta annan kod än regex för att
# generalisera. 



#######################################################################
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
    # tasks = [dna, sorted, hidden1_test, hidden2_test, equation, parentheses, sorted3]
    tasks = [hidden2]
    lines = ["p123r123oxyzgooop", "p123r123o123g12p"]
    for line in lines:
        print('Skriv in teststrängar:')
        for task in tasks:
            result = '' if re.search(task("progp"), line) else 'INTE '
            print('%s(): "%s" matchar %suttrycket "%s"' % (task.__name__, line, result, task("progp")))
if __name__ == '__main__': main()

