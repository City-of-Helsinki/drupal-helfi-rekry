import IndexFields from '../enum/IndexFields';

const now = Math.floor(Date.now() / 1000);

export const useDefaultQuery = () => {
  return {
    bool: {
      filter: [
        { term: { [IndexFields.LANGUAGE]: window.drupalSettings.path.currentLanguage || 'fi' } },
        {
          range: {
            [IndexFields.UNPUBLISH_ON]: {
              gte: now,
            },
          },
        },
      ],
    },
  };
};

export default useDefaultQuery;
