/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './style.css';
import AMPValidationStatus from '../amp-validation-status';
import { Error } from '../error';
import { store as blockValidationStore } from '../../store';

/**
 * Editor sidebar.
 */
export function Sidebar() {
	const { setIsShowingReviewed } = useDispatch(blockValidationStore);

	const { displayedErrors, hasReviewedValidationErrors, isShowingReviewed } =
		useSelect((select) => {
			const _isShowingReviewed =
				select(blockValidationStore).getIsShowingReviewed();

			return {
				displayedErrors: _isShowingReviewed
					? select(blockValidationStore).getValidationErrors()
					: select(
							blockValidationStore
					  ).getUnreviewedValidationErrors(),
				hasReviewedValidationErrors:
					select(blockValidationStore).getReviewedValidationErrors()
						?.length > 0,
				isShowingReviewed: _isShowingReviewed,
			};
		}, []);

	/**
	 * Focus the first focusable element when the sidebar opens.
	 */
	useEffect(() => {
		const element = document.querySelector(
			'.amp-sidebar a, .amp-sidebar button, .amp-sidebar input'
		);
		if (element) {
			element.focus();
		}
	}, []);

	return (
		<div className="amp-sidebar">
			<AMPValidationStatus />

			{0 < displayedErrors.length && (
				<ul className="amp-sidebar__errors-list">
					{displayedErrors.map((validationError, index) => (
						<li
							// Add `index` to key since not all errors have `clientId`.
							key={`${validationError.clientId}${index}`}
							className="amp-sidebar__errors-list-item"
						>
							<Error {...validationError} />
						</li>
					))}
				</ul>
			)}

			{hasReviewedValidationErrors && (
				<div className="amp-sidebar__options">
					<Button
						isLink
						onClick={() => setIsShowingReviewed(!isShowingReviewed)}
					>
						{isShowingReviewed
							? __('Hide reviewed issues', 'amp')
							: __('Show reviewed issues', 'amp')}
					</Button>
				</div>
			)}
		</div>
	);
}
