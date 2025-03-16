:- [kattio].

% Func to count amount of each letter
countLetterFreq(String, Counts) :-
    string_chars(String, Chars), % string -> ['s', 't', 'r', 'i', 'n', 'g']
    msort(Chars, SortedChars), % sort the list of chars alphabetically
    sort(SortedChars, UniqueChars), % sort the list and remove duplicates for all UNIQUE letters
    
    % Operations in line (ii) stored in line (i) and then stored in line (iii)
    findall(
        Count, 
        (member(Letter, UniqueChars), countChars(SortedChars, Letter, Count)),
        Counts
    ).

% helper func to count letter freq
countChars([], _, 0).
countChars([CharH | CharT], Letter, Counter) :-
    countChars(CharT, Letter, SubCount), 
    (CharH == Letter -> Counter is SubCount+1 ; Counter = SubCount).

% func to count amount of oddfreq letters
findOddFreq(String, CharCountList, OddFreqCount) :-
    countLetterFreq(String, CharCountList), % finds list of ALL letter counts
    findall(
        1,
        (member(LetterFreq, CharCountList), isOdd(LetterFreq)),
        OddList
    ),
    length(OddList, OddFreqCount).

% func to check if number is odd
isOdd(N) :- N mod 2 =:= 1.

% remove 1 letter from each odd letter if more than 1 odd letter freq
removedLetters(String, OddFreqCount, RemovedLetters) :-
    findOddFreq(String, CharCount, OddFreqCount),
    (OddFreqCount > 1 -> RemovedLetters is OddFreqCount-1 ; RemovedLetters = 0).

% main func
main :- 
    read_string(Peragram),
    removedLetters(Peragram, OddFreqCount, RemovedLetters), 
    write(RemovedLetters), nl.
