% Terminal input: Total dart score
% Terminal output: A possible combo of dart scores

% Run: 
%       swipl darscores.pl
%       ?- main.

% input: total dart score, possible numbers (1-20)

% import kattio file
:- [kattio].

% Possible throw values: single, double, triple [1-20]
throw(single, N, Score) :- between(1, 20, N), Score is N. % N is a number in the range 1-20
throw(double, N, Score) :- between(1, 20, N), Score is 2*N.
throw(triple, N, Score) :- between(1, 20, N), Score is 3*N.

% univ(=..): likes(john, mary)=..X => X = [likes, john, mary]
%            X =.. [likes, john, mary] => X = likes(john, mary)
% find combination for 1 throw
find_combination(TotalScore, [Throw1]) :-
    throw(Type1, N1, Score1), % set score1 to N1 (1-3 multiplied with 1-20) and unknown type (single, double, triple)
    Score1 =:= TotalScore, % check that score from throw func is same as total score
    Throw1 =.. [Type1, N1]. % set the throw itself to type from throw-func and val of N1
    
% find combination for 2 throws
find_combination(TotalScore, [Throw1, Throw2]) :-
    throw(Type1, N1, Score1), % set score1 to N1
    throw(Type2, N2, Score2), % set score2 to N2
    Score1+Score2 =:= TotalScore, % check the sum equal total score
    Throw1 =.. [Type1, N1], % define first throw
    Throw2 =.. [Type2, N2]. % define second throw

% find combination for 3 throws
find_combination(TotalScore, [Throw1, Throw2, Throw3]) :-
    throw(Type1, N1, Score1), % set score1 to N1
    throw(Type2, N2, Score2), % set score2 to N2
    throw(Type3, N3, Score3), % set score3 to N3
    Score1+Score2+Score3 =:= TotalScore, % check sum equal total score
    Throw1 =.. [Type1, N1], % define first throw
    Throw2 =.. [Type2, N2], % define second throw
    Throw3 =.. [Type3, N3]. % define third throw

% Print result correctly for Kattis
print_solution([]).
print_solution([H|T]) :-
    % Extract the type and number from the term (e.g., single(10))
    H =.. [Type, N], % format -> Type(N) instead of [Type, N]
    % Format the output as "Type N" (e.g., "single 10")
    write(Type), write(' '), write(N), nl,
    print_solution(T).

% main func
main :- 
    read_int(Score),
    (   find_combination(Score, Combination) 
    ->  print_solution(Combination), nl
    ;   write('impossible'), nl
    ), halt.