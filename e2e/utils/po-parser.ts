import * as fs from 'fs';
import * as path from 'path';

export async function loadTranslations(
  locale: string,
  domain = 'daily-dish-manager',
): Promise<Record<string, string>> {
  // Dynamischer Import für gettext-parser
  const gettextParser = await import('gettext-parser');

  const poPath = path.join(__dirname, '../..', 'languages', `${domain}-${locale}.po`);

  try {
    const poContent = fs.readFileSync(poPath);
    const parsedPO = gettextParser.po.parse(poContent);

    // Umwandeln in einfaches Objekt mit key/value Paaren
    const translations = {};
    const messages = parsedPO.translations[''] || {};

    Object.keys(messages).forEach((key) => {
      if (key === '') return; // Leere Schlüssel überspringen

      const translation = messages[key].msgstr[0];
      if (translation) {
        translations[key] = translation;
      }
    });

    return translations;
  } catch (error) {
    console.warn(`Could not load translations for ${locale}: ${error.message}`);
    return {};
  }
}

module.exports = { loadTranslations };
