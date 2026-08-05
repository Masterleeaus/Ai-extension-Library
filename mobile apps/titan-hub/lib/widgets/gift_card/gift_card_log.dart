import '../../backend/model/gift_card/my_gift_card_model.dart';
import '../../utils/basic_screen_imports.dart';
import '../text_labels/title_heading5_widget.dart';

class GiftCardLog extends StatelessWidget {
  const GiftCardLog({super.key, required this.giftCard});

  final GiftCard giftCard;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(
        bottom: Dimensions.heightSize,
      ),
      padding: EdgeInsets.symmetric(
        horizontal: Dimensions.paddingHorizontalSize * .4,
        vertical: Dimensions.paddingVerticalSize * .3,
      ),
      decoration: BoxDecoration(
        color: CustomColor.whiteColor,
        borderRadius: BorderRadius.circular(Dimensions.radius),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.start,
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Expanded(
            flex: 0,
            child: Container(
              padding: EdgeInsets.all(
                Dimensions.paddingSize * 1,
              ),
              decoration: BoxDecoration(
                color: Theme.of(context).scaffoldBackgroundColor,
                borderRadius: BorderRadius.circular(Dimensions.radius),
                image: DecorationImage(
                  image: NetworkImage(giftCard.cardImage),
                  filterQuality: FilterQuality.high,
                  fit: BoxFit.cover,
                ),
              ),
            ),
          ),
          horizontalSpace(Dimensions.marginSizeHorizontal * .5),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                TitleHeading2Widget(
                  text: giftCard.cardName,
                  fontSize: Dimensions.headingTextSize3 * .85,
                ),
                Row(
                  children: [
                    TitleHeading5Widget(
                      text: Strings.trxId,
                      fontSize: Dimensions.headingTextSize5 * .85,
                      opacity: 1,
                    ),
                    TitleHeading5Widget(
                      text: ': ',
                      fontSize: Dimensions.headingTextSize5 * .85,
                      opacity: 1,
                    ),
                    TitleHeading5Widget(
                      text: giftCard.trxId,
                      fontSize: Dimensions.headingTextSize5 * .85,
                      opacity: 1,
                    ),
                  ],
                ),
              ],
            ),
          ),
          Expanded(
            flex: 0,
            child: Row(
              children: [
                TitleHeading5Widget(
                  text: giftCard.cardTotalPrice,
                  fontSize: Dimensions.headingTextSize4,
                ),
                TitleHeading5Widget(
                  text: " ${giftCard.walletCurrency}",
                  fontSize: Dimensions.headingTextSize4,
                ),
              ],
            ),
          )
        ],
      ),
    );
  }
}
