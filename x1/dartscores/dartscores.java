// Run: 
//      javac dartscores.java
//      java dartscores.java

import java.util.*;

public class dartscores {
    public static void main(String[] args) {
        // retrieve user input for total score
        Scanner scanner = new Scanner(System.in);
        int totalScore = scanner.nextInt();
        scanner.close();

        // Lists for all possible throws
        List<String> throwNames = new ArrayList<>();
        List<Integer> throwValues = new ArrayList<>();

        // For 1-20, add stringnames to throwNames and int-vals to throwValues
        for (int i = 1; i <= 20; i++) {
            // Single values
            throwNames.add("single " + i);
            throwValues.add(i);

            // Double values
            throwNames.add("double " + i);
            throwValues.add(i * 2);

            // Triple values
            throwNames.add("triple " + i);
            throwValues.add(i * 3);
        }

        int n = throwValues.size();

        // check that totalscore is in correct interval
        if (totalScore > 0 && totalScore <= 180) {
            // Is the totalscore exactly same val as a possible one-throw val?
            // Loop through entire throwValues
            for (int i = 0; i < n; i++) {
                // is the totalscore exactly one stored value?
                if (throwValues.get(i) == totalScore) {
                    System.out.println(throwNames.get(i)); // print name
                    return;
                }
            }

            // Loop through entire throwValues
            for (int i = 0; i < n; i++) {
                // Loop through entire throwValues for a second throw
                for (int j = 0; j < n; j++) { // Start from i to avoid duplicate pairs
                    if (throwValues.get(i) + throwValues.get(j) == totalScore) {
                        System.out.println(throwNames.get(i)); // print first throw
                        System.out.println(throwNames.get(j)); // print second throw
                        return;
                    }
                }
            }

            // Try three throws
            for (int i = 0; i < n; i++) {
                for (int j = 0; j < n; j++) {
                    for (int k = 0; k < n; k++) { // Start from j to avoid duplicate triplets
                        if (throwValues.get(i) + throwValues.get(j) + throwValues.get(k) == totalScore) {
                            System.out.println(throwNames.get(i));
                            System.out.println(throwNames.get(j));
                            System.out.println(throwNames.get(k));
                            return;
                        }
                    }
                }
            }

            // If no valid combination found
            System.out.println("impossible");
        }
        else {
            System.out.println("Number not in interval 1-180");
        }
        
    }
}
