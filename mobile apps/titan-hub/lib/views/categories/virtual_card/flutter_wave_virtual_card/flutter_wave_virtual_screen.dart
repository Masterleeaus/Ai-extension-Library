import 'package:carousel_slider/carousel_slider.dart';
import 'package:flip_card/flip_card.dart';
import 'package:qrpay/extentions/custom_extentions.dart';
import 'package:qrpay/utils/basic_screen_imports.dart';
import 'package:qrpay/widgets/others/custom_glass/custom_glass_widget.dart';

import '../../../../backend/model/categories/virtual_card/virtual_card_flutter_wave/card_info_model.dart';
import '../../../../backend/utils/custom_loading_api.dart';
import '../../../../controller/categories/virtual_card/flutter_wave_virtual_card/virtual_card_controller.dart';
import '../../../../custom_assets/assets.gen.dart';
import '../../../../routes/routes.dart';
import '../../../../widgets/bottom_navbar/categorie_widget.dart';
import '../../../../widgets/bottom_navbar/transaction_history_widget.dart';

class FlutterWaveVirtualCardScreen extends StatelessWidget {
  const FlutterWaveVirtualCardScreen({super.key, required this.controller});

  final VirtualCardController controller;

  @override
  Widget build(BuildContext context) {
    return Obx(
      () => controller.isLoading
          ? const CustomLoadingAPI()
          : _bodyWidget(context),
    );
  }

  _bodyWidget(BuildContext context) {
    final data = controller.cardInfoModel.data.myCard;
    return Stack(
      children: [
        Column(
          children: [
            data.isEmpty ? _emptyCardWidget(context) : _cardWidget(context),
            _cardLimitShow(),
            data.isEmpty
                ? _createCardWidget(context)
                : _cardCategoriesWidget(context),
            const SizedBox()
          ],
        ),
        _draggableSheet(context),
      ],
    );
  }

  _draggableSheet(BuildContext context) {
    return DraggableScrollableSheet(
      builder: (_, scrollController) {
        return _recentTransWidget(context, scrollController);
      },
      initialChildSize: 0.45,
      minChildSize: 0.45,
      maxChildSize: 1,
    );
  }

  _cardWidget(BuildContext context) {
    final myCards = controller.cardInfoModel.data.myCard;
    return Obx(() {
      return Column(
        children: [
          CarouselSlider(
            items: myCards.map((data) {
              return Builder(
                builder: (BuildContext context) {
                  return FlipCard(
                    front: cardFontWidget(context, data),
                    back: cardBackWidget(context, data),
                  );
                },
              );
            }).toList(),
            carouselController: controller.carouselController,
            options: CarouselOptions(
              onPageChanged: (index, reason) {
                controller.current.value = index;
                controller.flutterWaveCardId.value = myCards[index].cardId;
              },
              height: MediaQuery.of(context).size.height * 0.24,
              viewportFraction: 1,
              initialPage: 0,
              enableInfiniteScroll: true,
              autoPlay: false,
              aspectRatio: 17 / 8,
              autoPlayInterval: const Duration(seconds: 5),
              autoPlayAnimationDuration: const Duration(seconds: 2),
              autoPlayCurve: Curves.fastOutSlowIn,
              enlargeCenterPage: true,
              scrollDirection: Axis.horizontal,
            ),
          ),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: controller.cardInfoModel.data.myCard
                .asMap()
                .entries
                .map((entry) {
              return controller.current.value == entry.key
                  ? Container(
                      width: Dimensions.widthSize * 1,
                      height: Dimensions.heightSize * 0.6,
                      margin: EdgeInsets.symmetric(
                          vertical: Dimensions.marginSizeVertical * 0.2,
                          horizontal: Dimensions.marginSizeHorizontal * 0.2),
                      decoration: const BoxDecoration(
                        shape: BoxShape.circle,
                        color: CustomColor.whiteColor,
                      ))
                  : Container(
                      width: Dimensions.widthSize * 0.7,
                      height: Dimensions.heightSize * 0.5,
                      margin: EdgeInsets.symmetric(
                          vertical: Dimensions.marginSizeVertical * 0.2,
                          horizontal: Dimensions.marginSizeHorizontal * 0.2),
                      decoration: BoxDecoration(
                        color: CustomColor.whiteColor.withOpacity(0.3),
                        shape: BoxShape.circle,
                      ),
                    );
            }).toList(),
          ),
        ],
      );
    });
  }

  _cardLimitShow() {
    return Padding(
      padding: EdgeInsets.only(top: Dimensions.paddingVerticalSize * 0.3),
      child: Row(
        mainAxisAlignment: mainCenter,
        children: [
          const TitleHeading3Widget(text: Strings.myCard),
          horizontalSpace(Dimensions.widthSize * 0.4),
          TitleHeading3Widget(
              text:
                  '${controller.cardInfoModel.data.myCard.length}/${controller.cardInfoModel.data.cardBasicInfo.cardCreateLimit}'),
        ],
      ),
    );
  }

  cardFontWidget(BuildContext context, MyCard data) {
    return Container(
      alignment: Alignment.center,
      padding: EdgeInsets.only(
        bottom: Dimensions.marginSizeVertical * 0.5,
        left: Dimensions.paddingSize * 0.5,
        right: Dimensions.paddingSize * 2,
      ),
      height: MediaQuery.of(context).size.height * 0.30,
      width: MediaQuery.of(context).size.width * 0.9,
      decoration: BoxDecoration(
        image: DecorationImage(
          image: AssetImage(Assets.card.frontPart.path),
          fit: BoxFit.cover,
        ),
        borderRadius: BorderRadius.circular(Dimensions.radius * 1.5),
      ),
      child: Padding(
        padding: EdgeInsets.symmetric(
            horizontal: Dimensions.marginSizeHorizontal * 0.7),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: crossStart,
          children: [
            verticalSpace(Dimensions.heightSize * 1.2),
            TitleHeading3Widget(
              text: Strings.payAndGo,
              color: CustomColor.whiteColor,
              fontWeight: FontWeight.w800,
              fontSize: Dimensions.headingTextSize4,
            ),
            verticalSpace(Dimensions.heightSize * 5),
            Text(
              data.cardPan.formatCardNumber(),
              style: TextStyle(
                fontFamily: "AgencyFB",
                fontSize: Dimensions.headingTextSize2 * 0.8,
                fontWeight: FontWeight.w700,
                color: CustomColor.whiteColor.withOpacity(0.6),
              ),
            ),
            verticalSpace(Dimensions.heightSize * 0.5),
            Row(
              children: [
                Column(
                  mainAxisAlignment: mainCenter,
                  crossAxisAlignment: crossStart,
                  children: [
                    CustomTitleHeadingWidget(
                      text:
                          '${data.expiration.split('-')[1]}/${data.expiration.split('-')[0].substring(data.expiration.split('-')[0].length - 2)}',
                      style: CustomStyle.f20w600pri.copyWith(
                        color: CustomColor.whiteColor,
                        fontSize: Dimensions.headingTextSize4,
                      ),
                    ),
                    CustomTitleHeadingWidget(
                      text: Strings.expiryDate,
                      style: CustomStyle.f20w600pri.copyWith(
                          color: CustomColor.whiteColor.withOpacity(0.6),
                          fontWeight: FontWeight.w500,
                          fontSize: Dimensions.headingTextSize5),
                    ),
                  ],
                ),
              ],
            )
          ],
        ),
      ),
    );
  }

  cardBackWidget(BuildContext context, MyCard data) {
    return Container(
      alignment: Alignment.topRight,
      padding: EdgeInsets.only(
          bottom: Dimensions.marginSizeVertical * 1.5,
          left: Dimensions.paddingSize * 0.5,
          right: Dimensions.paddingSize * 0.5),
      height: MediaQuery.of(context).size.height * 0.30,
      width: MediaQuery.of(context).size.width * 0.9,
      decoration: BoxDecoration(
        image: DecorationImage(
          image: AssetImage(Assets.card.backPart.path),
          fit: BoxFit.cover,
        ),
        borderRadius: BorderRadius.circular(Dimensions.radius * 1.5),
      ),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: crossStart,
        children: [
          verticalSpace(Dimensions.heightSize),
          horizontalSpace(Dimensions.widthSize),
          Column(
            mainAxisAlignment: mainCenter,
            children: [
              CustomTitleHeadingWidget(
                text: data.cvv,
                style: CustomStyle.f20w600pri
                    .copyWith(color: CustomColor.whiteColor),
              ),
              CustomTitleHeadingWidget(
                text: Strings.cvc,
                style: CustomStyle.f20w600pri.copyWith(
                    color: CustomColor.whiteColor.withOpacity(0.6),
                    fontWeight: FontWeight.w500,
                    fontSize: Dimensions.headingTextSize5),
              ),
            ],
          ),
        ],
      ),
    );
  }

  _emptyCardWidget(BuildContext context) {
    return FlipCard(
      front: emptyCardFontWidget(context),
      back: emptyCardBackWidget(context),
    );
  }

  emptyCardFontWidget(BuildContext context) {
    return Container(
      alignment: Alignment.center,
      margin: EdgeInsets.symmetric(
        horizontal: Dimensions.marginSizeHorizontal * 0.3,
      ),
      padding: EdgeInsets.only(
        bottom: Dimensions.marginSizeVertical * 0.5,
        left: Dimensions.paddingSize * 0.5,
        right: Dimensions.paddingSize * 2,
      ),
      height: MediaQuery.of(context).size.height * 0.30,
      width: MediaQuery.of(context).size.width,
      decoration: BoxDecoration(
        image: DecorationImage(
          image: AssetImage(Assets.card.frontPart.path),
          fit: BoxFit.cover,
        ),
        borderRadius: BorderRadius.circular(Dimensions.radius * 1.5),
      ),
      child: Padding(
        padding: EdgeInsets.only(left: Dimensions.marginSizeHorizontal * 0.4),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: crossStart,
          children: [
            verticalSpace(Dimensions.heightSize * 1.5),
            const TitleHeading3Widget(
              text: Strings.payAndGo,
              color: CustomColor.whiteColor,
              fontWeight: FontWeight.w800,
            ),
            verticalSpace(Dimensions.heightSize * 5.5),
            Text(
              "0000000000000000".formatCardNumber(),
              maxLines: 1,
              textAlign: TextAlign.start,
              style: TextStyle(
                fontFamily: "AgencyFB",
                fontSize: Dimensions.headingTextSize2,
                fontWeight: FontWeight.w700,
                color: CustomColor.whiteColor.withOpacity(0.6),
              ),
            ),
            verticalSpace(Dimensions.heightSize * 1.5),
            Row(
              children: [
                Column(
                  mainAxisAlignment: mainCenter,
                  crossAxisAlignment: crossStart,
                  children: [
                    CustomTitleHeadingWidget(
                      text: '00/00',
                      style: CustomStyle.f20w600pri
                          .copyWith(color: CustomColor.whiteColor),
                    ),
                    CustomTitleHeadingWidget(
                      text: Strings.expiryDate,
                      style: CustomStyle.f20w600pri.copyWith(
                          color: CustomColor.whiteColor.withOpacity(0.6),
                          fontWeight: FontWeight.w500,
                          fontSize: Dimensions.headingTextSize5),
                    ),
                  ],
                ),
              ],
            )
          ],
        ),
      ),
    );
  }

  emptyCardBackWidget(BuildContext context) {
    return Container(
      alignment: Alignment.topRight,
      padding: EdgeInsets.only(
          bottom: Dimensions.marginSizeVertical * 1.5,
          left: Dimensions.paddingSize * 0.5,
          right: Dimensions.paddingSize * 0.5),
      height: MediaQuery.of(context).size.height * 0.30,
      width: MediaQuery.of(context).size.width * 0.9,
      decoration: BoxDecoration(
          image: DecorationImage(
            image: AssetImage(Assets.card.backPart.path),
            fit: BoxFit.cover,
          ),
          borderRadius: BorderRadius.circular(Dimensions.radius * 1.5)),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: crossStart,
        children: [
          verticalSpace(Dimensions.heightSize),
          horizontalSpace(Dimensions.widthSize),
          Column(
            mainAxisAlignment: mainCenter,
            children: [
              CustomTitleHeadingWidget(
                text: "000",
                style: CustomStyle.f20w600pri
                    .copyWith(color: CustomColor.whiteColor),
              ),
              CustomTitleHeadingWidget(
                text: Strings.cvc,
                style: CustomStyle.f20w600pri.copyWith(
                    color: CustomColor.whiteColor.withOpacity(0.6),
                    fontWeight: FontWeight.w500,
                    fontSize: Dimensions.headingTextSize5),
              ),
            ],
          ),
        ],
      ),
    );
  }

  _cardCategoriesWidget(BuildContext context) {
    final data = controller.cardInfoModel.data.myCard;
    return Container(
      margin: EdgeInsets.only(
          bottom: Dimensions.marginSizeVertical,
          top: Dimensions.marginSizeVertical),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
        children: [
          CategoriesWidget(
            icon: Assets.icon.details,
            text: Strings.details,
            onTap: () {
              controller.getCardDetailsData(data.first.cardId);
              Get.toNamed(Routes.cardDetailsScreen);
            },
          ),
          CategoriesWidget(
              icon: Assets.icon.fund,
              text: Strings.fund,
              onTap: () {
                Get.toNamed(Routes.addFundScreen);
              }),
          Obx(
            () => controller.isMakeDefaultLoading
                ? const CustomLoadingAPI()
                : CategoriesWidget(
                    icon: Assets.icon.torch,
                    text: data[controller.current.value].isDefault
                        ? Strings.removeDefault
                        : Strings.makeDefault,
                    onTap: () {
                      controller.makeCardDefaultProcess(
                        data[controller.current.value].cardId,
                      );
                    },
                  ),
          ),
          CategoriesWidget(
            icon: Assets.icon.transaction,
            text: Strings.transaction,
            onTap: () {
              controller.getCardTransactionData(
                controller.cardInfoModel.data.myCard.first.cardId,
              );
            },
          ),
          if (controller.cardInfoModel.data.cardCreateAction) ...[
            CategoriesWidget(
              icon: Assets.icon.buyGift,
              text: Strings.createCard,
              onTap: () {
                Get.toNamed(Routes.crateStrowalletScreen);
              },
            ),
          ],
        ],
      ),
    );
  }

  _recentTransWidget(BuildContext context, ScrollController scrollController) {
    final data = controller.cardInfoModel.data.transactions;
    return data.isNotEmpty
        ? ListView(
            physics: const NeverScrollableScrollPhysics(),
            children: [
              Padding(
                padding: EdgeInsets.symmetric(
                    horizontal: Dimensions.paddingSize * 0.8),
                child: CustomTitleHeadingWidget(
                  text: Strings.recentTransactions,
                  style: Get.isDarkMode
                      ? CustomStyle.darkHeading3TextStyle.copyWith(
                          fontSize: Dimensions.headingTextSize2,
                          fontWeight: FontWeight.w600,
                        )
                      : CustomStyle.lightHeading3TextStyle.copyWith(
                          fontSize: Dimensions.headingTextSize2,
                          fontWeight: FontWeight.w600,
                        ),
                ),
              ),
              verticalSpace(Dimensions.widthSize),
              Container(
                height: MediaQuery.of(context).size.height,
                margin: EdgeInsets.symmetric(
                  horizontal: Dimensions.paddingSize * 0.8,
                ),
                child: ListView.builder(
                  controller: scrollController,
                  physics: const BouncingScrollPhysics(),
                  itemCount: data.length,
                  itemBuilder: (context, index) {
                    return TransactionWidget(
                      amount: data[index].requestAmount,
                      payable: data[index].payable,
                      title: data[index].transactionType,
                      dateText:
                          controller.getDay(data[index].dateTime.toString()),
                      transaction: data[index].trx,
                      monthText:
                          controller.getMonth(data[index].dateTime.toString()),
                    );
                  },
                ),
              )
            ],
          ).customGlassWidget()
        : Container();
  }

  _createCardWidget(BuildContext context) {
    return Container(
      margin: EdgeInsets.symmetric(
        horizontal: Dimensions.paddingSize * .3,
        vertical: Dimensions.paddingSize * .5,
      ),
      padding: EdgeInsets.symmetric(vertical: Dimensions.paddingSize * .5),
      child: PrimaryButton(
        title: Strings.createCard.tr,
        onPressed: () {
          Get.toNamed(Routes.buyCardScreen);
        },
        borderColor: CustomColor.primaryLightColor,
        buttonColor: CustomColor.primaryLightColor,
      ),
    );
  }
}
