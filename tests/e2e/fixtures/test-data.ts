/**
 * Test Data Fixtures
 * Reusable test data for E2E tests
 */

export const TestUsers = {
  validUser: {
    email: 'test@example.com',
    password: 'SecurePass123!',
  },
  anotherUser: {
    email: 'user2@example.com',
    password: 'AnotherSecure456!',
  },
} as const;

export const TestTexts = {
  /**
   * Valid text for generation (~5000 characters)
   */
  validGenerationText: `
Fotosynteza jest procesem, w którym rośliny przekształcają energię słoneczną w energię chemiczną.
Ten proces zachodzi w chloroplastach, organellach komórkowych zawierających chlorofil.

Główne etapy fotosyntezy:
1. Faza jasna - zachodzi w tylakoidach, wymaga światła
2. Faza ciemna (cykl Calvina) - zachodzi w stromie, nie wymaga bezpośrednio światła

W fazie jasnej następuje fotoliza wody, uwalnianie tlenu i produkcja ATP oraz NADPH.
W fazie ciemnej CO2 jest wiązany i przekształcany w glukozę.

Równanie ogólne fotosyntezy:
6CO2 + 6H2O + energia świetlna → C6H12O6 + 6O2

Fotosynteza jest kluczowa dla życia na Ziemi, ponieważ:
- Produkuje tlen niezbędny do oddychania
- Jest podstawą łańcuchów pokarmowych
- Wiąże dwutlenek węgla z atmosfery

Czynniki wpływające na szybkość fotosyntezy:
- Natężenie światła
- Temperatura
- Stężenie CO2
- Dostępność wody

Chlorofil a i b absorbują światło w zakresie niebieskim i czerwonym.
Zielone światło jest odbijane, dlatego rośliny wyglądają na zielone.

Produkty fotosyntezy są wykorzystywane przez rośliny do:
- Budowy struktur komórkowych (celuloza)
- Magazynowania energii (skrobia)
- Oddychania komórkowego

Oddychanie komórkowe jest procesem odwrotnym do fotosyntezy.
Zachodzi w mitochondriach i uwalnia energię z glukozy.
  `.repeat(4).trim(),

  /**
   * Text below minimum (999 characters)
   */
  tooShortText: 'To jest zbyt krótki tekst. '.repeat(35),

  /**
   * Text above maximum (10001 characters)
   */
  tooLongText: 'A'.repeat(10001),

  /**
   * Minimum valid text (1000 characters)
   */
  minimumValidText: 'Valid educational content about science. '.repeat(25),

  /**
   * Maximum valid text (10000 characters)
   */
  maximumValidText: 'Valid educational content about history and geography. '.repeat(182),
} as const;

export const TestSetNames = {
  valid: 'My Test Flashcard Set',
  tooShort: 'AB', // Less than 3 characters
  tooLong: 'A'.repeat(101), // More than 100 characters
  duplicate: 'Existing Set Name',
} as const;

/**
 * Expected AI-generated flashcard format
 */
export interface MockFlashcard {
  front: string;
  back: string;
}

export const MockGeneratedFlashcards: MockFlashcard[] = [
  { front: 'Czym jest fotosynteza?', back: 'Proces przekształcania energii słonecznej w energię chemiczną' },
  { front: 'Gdzie zachodzi fotosynteza?', back: 'W chloroplastach, organellach komórkowych zawierających chlorofil' },
  { front: 'Jakie są etapy fotosyntezy?', back: 'Faza jasna (w tylakoidach) i faza ciemna (cykl Calvina w stromie)' },
  { front: 'Co powstaje w fazie jasnej?', back: 'ATP, NADPH i tlen (z fotolizy wody)' },
  { front: 'Co to jest cykl Calvina?', back: 'Faza ciemna fotosyntezy, gdzie CO2 jest wiązany i przekształcany w glukozę' },
  { front: 'Równanie fotosyntezy', back: '6CO2 + 6H2O + światło → C6H12O6 + 6O2' },
  { front: 'Dlaczego rośliny są zielone?', back: 'Chlorofil odbija zielone światło, absorbuje niebieskie i czerwone' },
  { front: 'Czynniki wpływające na fotosyntezę', back: 'Natężenie światła, temperatura, stężenie CO2, dostępność wody' },
  { front: 'Do czego rośliny wykorzystują produkty fotosyntezy?', back: 'Budowa struktur (celuloza), magazynowanie energii (skrobia), oddychanie' },
  { front: 'Gdzie zachodzi oddychanie komórkowe?', back: 'W mitochondriach' },
];
