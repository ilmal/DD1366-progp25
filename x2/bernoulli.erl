% RUN FILE:
%           erl
%           c(bernoulli).
%           bernoulli:start().

-module(bernoulli).
-export([start/0, b/1, b_loop/2, binom/2]). % makes funcs public

% returns Nth bernoulli number
b(N) -> 
    Bs = b_loop(N, [1.0]), % Calls recursive loop with Bs[0]=1.0
    lists:nth(N+1,Bs). % retrieves (N+1)th bernoulli number since erl is 1-indexed

% helper func recursive loop - builds list of bernoulli nr
% If the list Bs has more than N elements, return list
b_loop(N,Bs) when length(Bs) > N ->
    Bs;
% otherwise, continue building Bs list
b_loop(N,Bs) ->
    M = length(Bs), % length of bernoulli numbers
    Ks = lists:seq(0,M-1), % 

    % fold from left, accumulate a sum
    Sum = lists:foldl(
        % lambdafunc with K and Acc
        fun(K, Acc) -> 
            Bk = lists:nth(K+1,Bs), % retrieve Bs[K+1] from list 
            Acc + binom(M+1,K) * Bk % call binom, multiply with Bk and add to sum (acc)
        end, 
        0, Ks),
    Bm = -Sum / (M+1), % divide with M+1 
    b_loop(N, Bs ++ [Bm]). % call on itself again with Bm appended

% binom function
binom(_,K) when K =:= 0 -> 1;
binom(N,K) when K > N -> 0; 
binom(N,K) -> 
    lists:foldl(fun(I,R) -> R * (N - I + 1) div I end, 1, lists:seq(1,K)). % måste använda fun som lambdafunc

% "main" function
start() ->
    % ~p prints binom(5,2)
    % ~n prints newline
    % io:format("~p~n", [b(4)]). 
    
     % Print first 10 Bernoulli numbers
    lists:foreach(fun(N) -> 
        io:format("B(~p) = ~p~n", [N, b(N)])
    end, lists:seq(0, 9)). % Loop through 0 to 9 and print each Bernoulli number
