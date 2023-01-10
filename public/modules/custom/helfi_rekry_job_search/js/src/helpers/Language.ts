import OptionType from '../types/OptionType';

export const getLanguageLabel = (key: string) => {
  switch (key.toString()) {
    case 'fi':
      return Drupal.t('Finnish');
    case 'sv':
      return Drupal.t('Swedish');
    case 'en':
      return Drupal.t('English');
  }
};

export const getInitialLanguage = (key: string[] | string = '', options: OptionType[]) => {
  return options.find((option: OptionType) => option?.value === key.toString()) || null;
};
