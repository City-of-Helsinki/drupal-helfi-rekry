import { ReactiveList, StateProvider } from '@appbaseio/reactivesearch';
import { useRef, useState } from 'react';

import Pagination from '../components/results/Pagination';
import ResultCard from '../components/results/ResultCard';
import ResultsHeader from '../components/results/ResultsHeader';
import ResultsSort from '../components/results/ResultsSort';
import IndexFields from '../enum/IndexFields';
import SearchComponents from '../enum/SearchComponents';
import useDefaultQuery from '../hooks/useDefaultQuery';
import useWindowDimensions from '../hooks/useWindowDimensions';
import Job from '../types/Job';
import OptionType from '../types/OptionType';

type ResultsData = {
  data: Job[];
};

const SORT_NEW = 'SORT_NEW';
const SORT_OLD = 'SORT_OLD';
const SORT_CLOSING = 'SORT_CLOSING';

const sortOptions: OptionType[] = [
  {
    value: SORT_NEW,
    label: Drupal.t('Newest first', {}, { context: 'HELfi Rekry job search' }),
  },
  {
    value: SORT_OLD,
    label: Drupal.t('Oldest first', {}, { context: 'HELfi Rekry job search' }),
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
  let sortBy: 'desc' | 'asc' = sort === SORT_NEW ? 'desc' : 'asc';

  return (
    <div ref={resultsWrapper} className='jobs-wrapper main-content'>
      <div className='layout-content'>
        <div className='jobs-header'>
          <StateProvider>{({ searchState }) => <ResultsHeader {...searchState} />}</StateProvider>
          <ResultsSort options={sortOptions} value={getSortValue()} setValue={setSort} />
        </div>
        <ReactiveList
          className='jobs-container'
          componentId={SearchComponents.RESULTS}
          dataField={dataField}
          onPageChange={onPageChange}
          pages={pages}
          pagination={true}
          defaultQuery={() => ({
            query: {
              ...defaultQuery,
            },
          })}
          render={({ data }: ResultsData) => (
            <ul className='jobs-listing jobs-listing--teasers'>
              {data.map((item: Job) => (
                <ResultCard key={item.uuid} {...item} />
              ))}
            </ul>
          )}
          renderNoResults={() => (
            <div className='jobs-listing__no-results'>
              {Drupal.t('No results found', {}, { context: 'Job search no results' })}
            </div>
          )}
          renderPagination={(props) => <Pagination {...props} />}
          showResultStats={false}
          sortBy={sortBy}
          size={10}
        />
      </div>
    </div>
  );
};

export default ResultsContainer;
