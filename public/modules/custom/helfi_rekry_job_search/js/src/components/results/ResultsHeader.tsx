import IndexFields from '../../enum/IndexFields';
import SearchComponents from '../../enum/SearchComponents';
import type SearchState from '../../types/SearchState';

const ResultsHeader = (searchState: SearchState) => {
  const { [SearchComponents.RESULTS]: results } = searchState;

  if (!results || !results?.hits?.total) {
    return null;
  }

  const resultString = results?.hits?.total
    ? Drupal.t(
        'vacancies (@announcements announcements)',
        { '@announcements': results.hits.total },
        { context: 'HELfi Rekry job search' }
      )
    : null;

  return (
    <span className='job-listing-search__count-container'>
      <span className='job-listing-search__count'>{results?.aggregations?.[IndexFields.NUMBER_OF_JOBS].value}</span>{' '}
      {resultString}
    </span>
  );
};

export default ResultsHeader;
