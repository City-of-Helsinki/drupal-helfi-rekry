import { ReactiveList } from '@appbaseio/reactivesearch';
import { useRef } from 'react';

import Pagination from '../components/results/Pagination';
import ResultCard from '../components/results/ResultCard';
import SearchComponents from '../enum/SearchComponents';
import useLanguageQuery from '../hooks/useLanguageQuery';
import useWindowDimensions from '../hooks/useWindowDimensions';
import Job from '../types/Job';

type ResultsData = {
  data: Job[];
};

const ResultsContainer = () => {
  const dimensions = useWindowDimensions();
  const languageFilter = useLanguageQuery();
  const resultsWrapper = useRef<HTMLDivElement | null>(null);
  const onPageChange = () => {
    if (!resultsWrapper.current) {
      return;
    }

    if (Math.abs(resultsWrapper.current.getBoundingClientRect().y) < window.pageYOffset) {
      resultsWrapper.current.scrollIntoView();
    }
  };

  const pages = dimensions.isMobile ? 3 : 5;

  return (
    <div ref={resultsWrapper} className='jobs-wrapper main-content'>
      <div className='layout-content'>
        <ReactiveList
          className='jobs-container'
          componentId={SearchComponents.RESULTS}
          dataField={'field_publication_starts'}
          onPageChange={onPageChange}
          pages={pages}
          pagination={true}
          defaultQuery={() => ({
            query: {
              ...languageFilter,
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
          sortBy={'desc'}
          size={10}
        />
      </div>
    </div>
  );
};

export default ResultsContainer;
