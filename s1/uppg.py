import re

# 1
def is_dna_sequence(sequence):
    pattern = r'^[ACGT]+$'
    return bool(re.match(pattern, sequence))

# 2
def is_sorted_descending(sequence):
    pattern = r'^9*8*7*6*5*4*3*2*1*0*$'
    return bool(re.match(pattern, sequence))

# 3
def contains_substring(s, x):
    pattern = fr'^.*{re.escape(x)}.*$'
    return bool(re.match(pattern, s))


if __name__ == "__main__":
    # DNA-sekvens
    print(is_dna_sequence("ACGTACGT"))  # True
    print(is_dna_sequence("ACGTX"))     # False

    # sorterade tal
    print(is_sorted_descending("9876543210"))  # True
    print(is_sorted_descending("123"))         # False

    # förekomst av söksträng x
    x = "progp"
    print(contains_substring("popororpoprogpepor", x))  # True
    print(contains_substring("programmeringsparadigm", x))  # False


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
