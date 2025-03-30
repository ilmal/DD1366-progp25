// Run: 
//      javac busnumbers.java
//      java busnumbers.java

package busnumbers;

import java.util.*;

public class busnumbers {
    static int numberOfBuses = 0;
    static List<Integer> listOfBuslines = new ArrayList<Integer>();
    static List<String> formattedListOfBuslines = new ArrayList<String>();
    
    // Function that takes string of numbers and returns a list of ints 
    public static List<Integer> convertToArraylist (String numbers) {
        // store each string split by single spaces in numberStrings array
        String[] numberStrings = numbers.split("\s"); // Separate with regex for single spaces
        
        // Loop the list of strings and convert each element to int and add to global int list
        for (int i = 0; i < numberStrings.length; i++) {
            listOfBuslines.add(Integer.parseInt(numberStrings[i]));
        }
        // Return the int list
        return listOfBuslines;
    }

    public static List<String> findConsecutive(List<Integer> inputNumbers) {
        List<String> formattedNumbers = new ArrayList<String>(); // formatted numbers in a stringlist
        Collections.sort(inputNumbers); // sort input numbers for correct output order

        // loop through the input numbers
        for (int i = 0; i < inputNumbers.size(); i++) {
            int currentNum = inputNumbers.get(i); // Retrieve current number
            List<Integer> consecNums = new ArrayList<Integer>(); // List to store consec numbers
            consecNums.add(currentNum); // add current to consec list

            // while next num is consecutive, keep adding to consecNums
            while (i+1 < inputNumbers.size() && inputNumbers.get(i+1) == inputNumbers.get(i)+1) {
                i++;
                consecNums.add(inputNumbers.get(i));
            }
            // If consecNums has more than 2 elems, its worth it to save space
            if (consecNums.size() > 2) {
                String firstNum = String.valueOf(consecNums.get(0)); // stringify first num
                String lastNum = String.valueOf(consecNums.get(consecNums.size()-1)); // stringify last num
                String formatted = firstNum + "-" + lastNum; // concat to correct format

                formattedNumbers.add(formatted); // add formatted string to list of formatted nums
            }
            // otherwise, add string as single elems
            else {
                // for each elem in consecNums (1 or 2 elems)
                for (int num : consecNums) {
                    String current = String.valueOf(num); // stringify Int
                    formattedNumbers.add(current); // add to formatted nums
                }
            }
        }
        return formattedNumbers; // return list of formatted nums
    }
    
    // func to print out array in one line
    public static void printBuslines(List<String> inputStrings) {
        for (int i = 0; i < inputStrings.size(); i++) {
            String currentString = inputStrings.get(i);
            System.out.print(currentString + " "); // print in one line and spece between
        }
        System.out.println(); // create newline after printing out all buslines
    }
    
    // Main method for input in terminal
    public static void main(String[] args) {
        // Scanner to read lines from terminal input
        Scanner scanner = new Scanner(System.in);

        // store terminal input in variable buslines (list length)
        String buslines = scanner.nextLine();
        numberOfBuses = Integer.parseInt(buslines); // set global variable from 0 to input num

        // store terminal input in variable input2 (list of buslines)
        String buslist = scanner.nextLine(); 
        listOfBuslines = convertToArraylist(buslist); // change value of global list  
        formattedListOfBuslines = findConsecutive(listOfBuslines); // reformat listOfBuslines with func

        // print in correct way
        printBuslines(formattedListOfBuslines);

        // close scanner
        scanner.close();
    }
}