import 'package:carousel_slider/carousel_slider.dart';

import '../../../../controller/categories/virtual_card/strowallet_card/strowallelt_info_controller.dart';
import '../../../../controller/navbar/dashboard_controller.dart';
import '../../../../custom_assets/assets.gen.dart';
import '../../../../utils/basic_screen_imports.dart';
import 'card_widget.dart';

class StrowalletSlider extends StatelessWidget {
  StrowalletSlider({
    super.key,
  });
  final myCardController = Get.put(VirtualStrowalletCardController());
  final controller = Get.find<DashBoardController>();
  @override
  Widget build(BuildContext context) {
    var myCards = myCardController.strowalletCardModel.data.myCards;
    return myCards.isNotEmpty
        ? Obx(() {
            return Column(
              children: [
                CarouselSlider(
                  items: myCards.map((card) {
                    return Builder(
                      builder: (BuildContext context) {
                        return CardWidget(
                          cardNumber: card.cardNumber,
                          expiryDate: card.expiry,
                          balance: card.balance.toString(),
                          validAt: card.expiry,
                          cvv: card.cvv,
                          logo: card.siteLogo,
                          cardBgNetwork: myCardController
                              .strowalletCardModel.data.cardBasicInfo.cardBg,
                        );
                      },
                    );
                  }).toList(),
                  carouselController: controller.carouselController,
                  options: CarouselOptions(
                    onPageChanged: (index, reason) {
                      controller.current.value = index;
                      myCardController.strowalletCardId.value =
                          myCards[index].cardId;
                      debugPrint(myCardController.strowalletCardId.value);
                      //! Custom Dot indicator State
                    },
                    height: MediaQuery.of(context).size.height * 0.28,
                    viewportFraction: 1,
                    initialPage: 0,
                    enableInfiniteScroll: true,
                    autoPlay: false,
                    aspectRatio: 17 / 9,
                    autoPlayInterval: const Duration(seconds: 5),
                    autoPlayAnimationDuration: const Duration(seconds: 2),
                    autoPlayCurve: Curves.fastOutSlowIn,
                    enlargeCenterPage: true,
                    scrollDirection: Axis.horizontal,
                  ),
                ),
                verticalSpace(Dimensions.heightSize),
                Row(
                  mainAxisAlignment: mainCenter,
                  children: [
                    const TitleHeading3Widget(text: Strings.myCard),
                    horizontalSpace(Dimensions.widthSize * 0.4),
                    TitleHeading3Widget(
                        text:
                            '${myCardController.strowalletCardModel.data.myCards.length}/${myCardController.strowalletCardModel.data.cardBasicInfo.cardCreateLimit}'),
                  ],
                ),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: myCardController.strowalletCardModel.data.myCards
                      .asMap()
                      .entries
                      .map((entry) {
                    return controller.current.value == entry.key
                        ? Container(
                            width: Dimensions.widthSize * 1,
                            height: Dimensions.heightSize * 0.6,
                            margin: EdgeInsets.only(
                              bottom: Dimensions.marginSizeVertical * 0.2,
                              left: Dimensions.marginSizeHorizontal * 0.2,
                              right: Dimensions.marginSizeHorizontal * 0.2,
                            ),
                            decoration: const BoxDecoration(
                              shape: BoxShape.circle,
                              color: CustomColor.whiteColor,
                            ))
                        : Container(
                            width: Dimensions.widthSize * 0.7,
                            height: Dimensions.heightSize * 0.5,
                            margin: EdgeInsets.only(
                              bottom: Dimensions.marginSizeVertical * 0.2,
                              left: Dimensions.marginSizeHorizontal * 0.2,
                              right: Dimensions.marginSizeHorizontal * 0.2,
                            ),
                            decoration: BoxDecoration(
                              color: CustomColor.whiteColor.withOpacity(0.3),
                              shape: BoxShape.circle,
                            ),
                          );
                  }).toList(),
                ),
              ],
            );
          })
        : Column(
            children: [
              CardWidget(
                cardNumber: 'xxxx xxxx xxxx xxxx',
                expiryDate: 'xx/xx',
                balance: 'xx',
                validAt: 'xx',
                cvv: 'xxx',
                logo: Assets.logo.logo.path,
                isNetworkImage: false,
                cardBgNetwork: myCardController
                    .strowalletCardModel.data.cardBasicInfo.cardBg,
              ),
              verticalSpace(Dimensions.heightSize * 0.5),
              Row(
                mainAxisAlignment: mainCenter,
                children: [
                  const TitleHeading3Widget(text: Strings.myCard),
                  horizontalSpace(Dimensions.widthSize * 0.4),
                  TitleHeading3Widget(
                      text:
                          '${myCardController.strowalletCardModel.data.myCards.length}/${myCardController.strowalletCardModel.data.cardBasicInfo.cardCreateLimit}'),
                ],
              ),
            ],
          );
  }
}
