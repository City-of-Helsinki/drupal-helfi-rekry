import type SearchState from '../../types/SearchState';

const ResultsHeader = (searchState: SearchState) => {
  const { results } = searchState;

  if (!results) {
    return null;
  }

  const resultString = Drupal.t(
    'vacancies (@announcements announcements)',
    { '@announcements': results.hits?.total },
    { context: 'HELfi Rekry job search' }
  );

  return (
    <div>
      <strong>{results.hits?.total}</strong> {resultString}
    </div>
  );
};

export default ResultsHeader;
