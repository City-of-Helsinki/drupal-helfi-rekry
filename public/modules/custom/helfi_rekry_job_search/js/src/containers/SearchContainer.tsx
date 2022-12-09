import { LoadingSpinner } from 'hds-react';
import { Suspense } from 'react';

import FormContainer from './FormContainer';
import ResultsContainer from './ResultsContainer';

const SearchContainer = () => {
  return (
    <div className='recruitment-search'>
      {/* For async atoms that need to load option values from elastic*/}
      <Suspense fallback={<LoadingSpinner />}>
        <FormContainer />
      </Suspense>
      {!drupalSettings?.helfi_rekry_job_search?.results_page_path && <ResultsContainer />}
    </div>
  );
};

export default SearchContainer;
