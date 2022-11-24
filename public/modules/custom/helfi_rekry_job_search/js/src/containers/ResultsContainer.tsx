import Pagination from '../components/results/Pagination';
import ResultCard from '../components/results/ResultCard';

type ResultsContainerProps = {
  hits?: {
    hits: any[];
    total: {
      value: number;
    };
  };
  isLoading: boolean;
  size: number;
};

const ResultsContainer = ({ hits, isLoading, size }: ResultsContainerProps) => {
  if (!hits?.hits) {
    return <div> _NO_RESULTS_</div>;
  }

  const results = hits.hits;
  const total = hits.total.value;
  const pages = Math.floor(total / size);
  const addLastPage = total % size;

  return (
    <div>
      {isLoading && <div>loading...</div>}
      {results.map((hit: any) => (
        <ResultCard key={hit._id} {...hit._source} />
      ))}
      <Pagination pages={5} totalPages={addLastPage ? pages + 1 : pages} />
    </div>
  );
};

export default ResultsContainer;
