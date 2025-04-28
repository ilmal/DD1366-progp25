       *> RUN FILE:
       *>          cobc -x -free bernoulli.cbl

       IDENTIFICATION DIVISION.
       PROGRAM-ID. bernoulli. *> programmets namn
       
       DATA DIVISION.

       *> spara ALLA temporära variabler här av någon anledning >:(
       WORKING-STORAGE SECTION.
       01 k    PIC 9(2). *> för 'k' i for-loop för binom
       01 n    PIC 9(2) VALUE 9. *>  max bernoulli tal att räkna
       01 m    PIC 9(2).  *> yttre loop counter för bernoulli func
       01 i    PIC 9(2). *> inre loop counter för bernoulli func
       
       01 r    PIC S9(5)V9(6) VALUE 1. *> sparar result från binom
       01 temp PIC S9(5)V9(6). *> temporär var för beräkningar
       
       01 b. 
           05 B-item PIC S9(5)V9(6) OCCURS 10 TIMES INDEXED BY idx. 
           *> array att spara bernoullitalen 
           *> 5 digits innan och efter
           *> INDEXED BY för att underlätta åtkomst till element
       
       PROCEDURE DIVISION.
           *> main program logic
           DISPLAY "COBOL"
           PERFORM bernoulli *> kallar på bernoulli func
           STOP RUN. *> avslutar program
       
       bernoulli.
           MOVE 1.0 TO B-item(1)  *> B_0 = 1
           DISPLAY "B(0) = " B-item(1) *> print

           *> yttre loop 
           PERFORM VARYING m FROM 1 BY 1 UNTIL m > n
               MOVE 0 TO temp  *> reset temporär var för summering

               *> inre loop
               PERFORM VARYING k FROM 0 BY 1 UNTIL k = m
                   PERFORM binom *> kalla på binom för att uppdatera r
                   COMPUTE temp = temp - r * B-item(k + 1) *> räkna ut med nytt r
               END-PERFORM
               COMPUTE B-item(m + 1) = temp / (m + 1) *> dela med m+1
               DISPLAY "B(" m ") = " B-item(m + 1) *> print


           END-PERFORM.
       
       binom.
           MOVE 1 TO r
           PERFORM VARYING i FROM 1 BY 1 UNTIL i > k
               COMPUTE r ROUNDED = r * (m + 1 - i + 1) / i *> 1-indexerat så m+1
           END-PERFORM
           EXIT.
