import { ReactiveList, StateProvider } from '@appbaseio/reactivesearch';
import { useRef, useState } from 'react';

import Pagination from '../components/results/Pagination';
import ResultCard from '../components/results/ResultCard';
import ResultsHeader from '../components/results/ResultsHeader';
import ResultsSort from '../components/results/ResultsSort';
import IndexFields from '../enum/IndexFields';
import SearchComponents from '../enum/SearchComponents';
import useDefaultQuery from '../hooks/useDefaultQuery';
import useSearchParams from '../hooks/useSearchParams';
import useWindowDimensions from '../hooks/useWindowDimensions';
import Job from '../types/Job';
import OptionType from '../types/OptionType';

type ResultsData = {
  data: Job[];
};

const SORT_NEW = 'SORT_NEW';
const SORT_CLOSING = 'SORT_CLOSING';

const sortOptions: OptionType[] = [
  {
    value: SORT_NEW,
    label: Drupal.t('Newest first', {}, { context: 'HELfi Rekry job search' }),
  },
  {
    value: SORT_CLOSING,
    label: Drupal.t('Application period ending', {}, { context: 'HELfi Rekry job search' }),
  },
];

const ResultsContainer = () => {
  const [sort, setSort] = useState<string>(SORT_NEW);
  const dimensions = useWindowDimensions();
  const defaultQuery = useDefaultQuery();
  const [params] = useSearchParams();
  const resultsWrapper = useRef<HTMLDivElement | null>(null);
  const onPageChange = () => {
    if (!resultsWrapper.current) {
      return;
    }

    if (Math.abs(resultsWrapper.current.getBoundingClientRect().y) < window.pageYOffset) {
      resultsWrapper.current.scrollIntoView({ behavior: 'smooth' });
    }
  };

  const pages = dimensions.isMobile ? 3 : 5;

  const getSortValue = () => {
    return sortOptions.find((option: OptionType) => option.value === sort);
  };

  let dataField = sort === SORT_CLOSING ? IndexFields.UNPUBLISH_ON : IndexFields.PUBLICATION_STARTS;
  let sortBy: 'desc' | 'asc' = sort === SORT_CLOSING ? 'asc' : 'desc';

  return (
    <div ref={resultsWrapper}>
      <div className='job-listing-search__result-actions'>
        <StateProvider>{({ searchState }) => <ResultsHeader {...searchState} />}</StateProvider>
        <ResultsSort options={sortOptions} value={getSortValue()} setValue={setSort} />
      </div>
      <ReactiveList
        className='jobs-container'
        componentId={SearchComponents.RESULTS}
        // Seems like a bug in ReactiveSearch.
        // Setting defaultPage prop does nothing.
        // currentPage props used in source but missing in props type declarations.
        // @ts-ignore
        currentPage={params.page}
        dataField={dataField}
        defaultQuery={() => ({
          aggs: {
            [IndexFields.NUMBER_OF_JOBS]: {
              sum: {
                field: IndexFields.NUMBER_OF_JOBS,
              },
            },
          },
          query: {
            ...defaultQuery,
          },
        })}
        onPageChange={onPageChange}
        pages={pages}
        pagination={true}
        render={({ data }: ResultsData) => (
          <div className='job-listing-search__result--list'>
            {data.map((item: Job) => (
              <ResultCard key={item.uuid[0]} {...item} />
            ))}
          </div>
        )}
        renderNoResults={() => (
          <div className='job-listing-search__no-results'>
            <div>
              <strong>{Drupal.t('No results found', {}, { context: 'Job search: no results title' })}</strong>
            </div>
            <div>
              {
                (Drupal.t('No results found with your selections. Remove some of the filters.'),
                {},
                { context: 'Job search: no results text' })
              }
            </div>
          </div>
        )}
        renderPagination={(props) => <Pagination {...props} />}
        showResultStats={false}
        sortBy={sortBy}
        size={10}
      />
    </div>
  );
};

export default ResultsContainer;
