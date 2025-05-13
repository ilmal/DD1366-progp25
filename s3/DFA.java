import java.util.*;

public class DFA {
    int dfaStateCount; // amount of states in total 
    int dfaStartState; // q0: number for starting state
    List<Integer> states = new ArrayList<Integer>(); // Q: store all states
    List<Integer> acceptingStates = new ArrayList<Integer>(); // F: store accepting states
    Map<Integer, Map<Character, Integer>> transitions = new HashMap<>(); // Store all transitions (To, (Char, From))
    
    // klass för att behandla tuplar
    public static class Tuple {
        // fields
        int state;   
        String str;
        int depth;

        // konstruktor - skapar en tupel med state, string, och depth
        Tuple(int state, String str, int depth) {
            this.state = state;
            this.str = str;
            this.depth = depth;
        }
    }

    // konstruktor för DFA med antal states och startstate
    public DFA(int stateCount, int startState) {
        // sätt count och state till värdet av inparametrar
        dfaStateCount = stateCount;
        dfaStartState = startState; 

        // Lägg till alla states i DFA:n till Q  
        for(int i = 0; i < stateCount; i++) {
            states.add(i); 
        }
    }

    /*  funktion som sparar alla accepting states i listan 
        acceptingStates */
    public void setAccepting(int state) {
        acceptingStates.add(state);
    }

    /* funktion som lägger till en övergång från en state till en annan i listan transitions 
        int from = state som vi börjar på
        int to = state som vi kan gå till
        char c = bokstaven som leder dit
    */ 
    public void addTransition(int from, int to, char c) { 
        /* Kollar om transitions redan lagrar värden för 'from' parametern, 
            om inte skapa en ny hashmap för att lagra värden och lägg till c och 'to' i den
        */
        transitions.computeIfAbsent(from, k -> new HashMap<>()).put(c, to); 
    }
    
    /* Hitta alla strängar som DFA:n accepterar mha dfs */
    public List<String> getAcceptingStrings(int maxCount) {
        Deque<Tuple> stack = new LinkedList<>(); // Stack for DFS
        List<String> acceptedStrings = new ArrayList<>(); // Store accepted strings
        Set<String> visited = new HashSet<>(); // Track state-string pairs to detect cycles
        Map<Integer, Boolean> canReachAccepting = new HashMap<>(); // Cache states that can reach accepting states

        /* This method computes a map canReachAccpeting so we already know 
         * which states in out DFA can actually reach an accpeting state to 
         * save time */
        computeReachableAcceptingStates(canReachAccepting);


        // Handle the case where the start state is accepting (empty string)
        if (acceptingStates.contains(dfaStartState)) {
            acceptedStrings.add(""); // add empty string if startstate is an accepting state
            // return list of accepted strings if reached maxCount
            if (acceptedStrings.size() == maxCount) {
                return acceptedStrings;
            }
        }

        /* add startstate to stack with empty string and depth 0*/
        stack.push(new Tuple(dfaStartState, "", 0));
        visited.add(dfaStartState + ":"); // Initial state with empty string

        /* when stack isn't empty and we havent reached maxcount */
        while (!stack.isEmpty() && acceptedStrings.size() < maxCount) {
            Tuple current = stack.pop(); // pop the top of stack (current)
            int currentState = current.state; // retrieve current state from popped tuple
            String currentString = current.str; // retrieve current string from popped tuple
            int currentDepth = current.depth; // retrieve current depth from popped tuple

            // Check if there are transitions from the current state
            if (transitions.containsKey(currentState)) {
                // retrieve each inner map entry for current state (aka each transition) 
                for (Map.Entry<Character, Integer> entry : transitions.get(currentState).entrySet()) {
                    char symbol = entry.getKey(); // retrieve symbol of transition
                    int nextState = entry.getValue(); // retrieve next state
                    String newString = currentString + symbol; // concatenate string
                    String stateStringKey = nextState + ":" + newString; 

                    // Skip if depth exceeded (for speed)
                    int maxDepth = 100;
                    if (currentDepth >= maxDepth) {
                        continue;
                    }

                    // Check if next state is an accepting state
                    if (acceptingStates.contains(nextState)) {
                        acceptedStrings.add(newString); // add new string to accepted string list
                        // return list if reached maxlimit
                        if (acceptedStrings.size() == maxCount) {
                            return acceptedStrings;
                        }
                    }

                    // Check for cycle and handle if it can lead to an accepting state
                    if (visited.contains(stateStringKey)) {
                        /*  go in if-statement only if nextState associated with true, otherwise false
                            If there is a loop with an accepting state, keep generating strings from the 
                            same loop until it reached maxlimit 
                        */
                        if (canReachAccepting.getOrDefault(nextState, false)) {
                            // Generate strings by repeating the cycle
                            generateCycleStrings(nextState, newString, currentDepth, maxDepth, acceptedStrings, maxCount, canReachAccepting);
                            // return if maxcount reached
                            if (acceptedStrings.size() == maxCount) {
                                return acceptedStrings;
                            }
                        }
                        continue; // Skip further exploration to avoid redundant paths
                    }

                    // Only explore states that can lead to accepting states
                    if (canReachAccepting.getOrDefault(nextState, false)) {
                        // push nextState with new info to stack with updated depth
                        stack.push(new Tuple(nextState, newString, currentDepth + 1)); 
                        visited.add(stateStringKey); // add state and string to visited list to keep track
                    } 
                }
            } 
        }

        return acceptedStrings;
    }

    // Helper method to compute which states can reach an accepting state
    private void computeReachableAcceptingStates(Map<Integer, Boolean> canReachAccepting) {
        // Initialize with false for every state in DFA
        for (int state : states) {
            canReachAccepting.put(state, false);
        }
        // Set all accepting states in the DFA to true 
        for (int state : acceptingStates) {
            canReachAccepting.put(state, true);
        }
        // Backward DFS to find all states that can reach an accepting state
        boolean changed; // bool flag to track if current state can reach an accpeting state
        do {
            changed = false; // set to false first
            // for each state in Q for the DFA 
            for (int currState : states) {
                /* IF current state has value false in map 
                        (all states apart from acc. states are initialized to false)
                        and current state has actual transitions 
                   IGNORE if-statement when state is already in canReachAccepting 
                */
                if (!canReachAccepting.get(currState) && transitions.containsKey(currState)) {
                    /* For every state (nextState) that has a transition from current state */
                    for (int nextState : transitions.get(currState).values()) {
                        /* If nextState is mapped with the bool value true */
                        if (canReachAccepting.get(nextState)) {
                            /* Add current state to map canReachAccepting with "true" */
                            canReachAccepting.put(currState, true); 
                            changed = true; // reset by setting flag to true
                            break; // if there is a path to accepting found for currState, break to save time
                        }
                    }
                }
            }
        } while (changed);
    }

    // Helper method to generate strings by repeating a cycle/loop in the DFA with an accepting state
    /* Parameters: int cycleState = 
     *             String baseString = 
     *             int currentDepth = 
     *             int maxDepth = 
     *             List<String> acceptedStrings = 
     *             int maxCount = 
     *             Map<Integer,Boolean> canReachAccepting = 
     */
    private void generateCycleStrings(int cycleState, String baseString, int currentDepth, int maxDepth,
                                     List<String> acceptedStrings, int maxCount, Map<Integer, Boolean> canReachAccepting) {
        Deque<Tuple> cycleStack = new LinkedList<>(); // create a separate stack for cycle string counter

        // push tuple to stack with input parameters 
        cycleStack.push(new Tuple(cycleState, baseString, currentDepth)); 

        // as long as we havent reached maximum and stack isnt empty...
        while (!cycleStack.isEmpty() && acceptedStrings.size() < maxCount) {
            Tuple current = cycleStack.pop(); // the top of stack is the current tuple
            int state = current.state; // retrieve state
            String str = current.str; // retrieve string
            int depth = current.depth; // retrieve depth

            // check if we reached max depth (speed)
            if (depth >= maxDepth) {
                continue;
            }

            // if accpeting state list contains current state
            if (acceptingStates.contains(state)) {
                acceptedStrings.add(str); // add current string to accepted strings
                // another maxcount check 
                if (acceptedStrings.size() == maxCount) {
                    return;
                }
            }
            // if transition list contains transitions from current state
            if (transitions.containsKey(state)) {
                // retrieve every inner map that contains info about the transition to next states
                for (Map.Entry<Character, Integer> entry : transitions.get(state).entrySet()) {
                    int nextState = entry.getValue(); // retrieve to-state of each transition
                    // if next staate is paired with true, continue, otherwise skip
                    if (canReachAccepting.getOrDefault(nextState, false)) {
                        String newString = str + entry.getKey(); // retrieve letter/letters from transition
                        cycleStack.push(new Tuple(nextState, newString, depth + 1)); // push new tuple with new info
                    }
                }
            }
        }
    }
}